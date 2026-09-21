<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\MasterclassRegistration;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Customer-list Custom Audiences on the Meta ad account.
 *
 * Two lists: paid Accelerator buyers (the seed for a Lookalike of the ideal customer)
 * and TAAB masterclass registrants (warm leads for retargeting, and an exclusion for
 * cold campaigns). Rows are hashed by MetaUserData, so Meta only ever sees SHA-256s.
 *
 * The audience ids live in the Setting store, written the first time the sync runs,
 * so creating them needs no deploy. Re-uploading the same hashes is a no-op on Meta's
 * side, which is what makes the daily sync idempotent.
 */
class MetaAudiences
{
    public const LISTS = [
        'buyers' => [
            'setting' => 'meta_audience_buyers_id',
            'name' => 'AJBuildAI - Accelerator buyers (app sync)',
            'description' => 'Paid AI Automation Accelerator enrollments. Synced daily by meta:sync-audiences.',
        ],
        'leads' => [
            'setting' => 'meta_audience_leads_id',
            'name' => 'AJBuildAI - TAAB masterclass registrants (app sync)',
            'description' => 'Everyone who registered for a TAAB masterclass. Synced daily by meta:sync-audiences.',
        ],
    ];

    /** Meta's per-request ceiling for /users uploads. */
    public const BATCH = 10000;

    /** Graph error subcode when the ad account has not accepted the Custom Audience Terms. */
    public const TERMS_NOT_ACCEPTED = 1870090;

    public function isConfigured(): bool
    {
        return (bool) (config('services.meta.access_token') && config('services.meta.ad_account_id'));
    }

    public function termsUrl(): string
    {
        return 'https://business.facebook.com/ads/manage/customaudiences/tos/?act='.config('services.meta.ad_account_id');
    }

    /**
     * Hashed rows for a list, one per email (the latest row wins so the freshest phone
     * and name are what Meta gets), plus the counts the command reports.
     *
     * @return array{rows: array<int, array<int, string>>, source: int, duplicates: int, no_email: int}
     */
    public function rowsFor(string $list): array
    {
        $people = match ($list) {
            'buyers' => Enrollment::query()->where('status', 'paid')->orderBy('paid_at')->orderBy('id')->get()
                ->map(fn (Enrollment $e) => MetaUserData::fromEnrollment($e)),
            'leads' => MasterclassRegistration::query()->orderBy('id')->get()
                ->map(fn (MasterclassRegistration $r) => MetaUserData::fromRegistration($r)),
            default => throw new \InvalidArgumentException("Unknown audience list '{$list}'."),
        };

        $source = $people->count();
        $withEmail = $people->filter(fn (array $p) => ! empty($p['email']));
        $unique = $withEmail->keyBy('email');   // keyBy keeps the LAST row per key

        return [
            'rows' => $unique->values()->map(fn (array $p) => MetaUserData::audienceRow($p))->all(),
            'source' => $source,
            'duplicates' => $withEmail->count() - $unique->count(),
            'no_email' => $source - $withEmail->count(),
        ];
    }

    /**
     * The audience id for a list: from Setting, else created on the ad account and
     * stored. Null (logged) when creation fails - nothing is stored on failure, so the
     * next run tries again rather than uploading into a half-made audience.
     *
     * @return array{id: ?string, created: bool, error: ?array}
     */
    public function ensureAudience(string $list): array
    {
        $spec = self::LISTS[$list];

        if ($id = Setting::get($spec['setting'])) {
            return ['id' => (string) $id, 'created' => false, 'error' => null];
        }

        $response = $this->post(sprintf('act_%s/customaudiences', config('services.meta.ad_account_id')), [
            'name' => $spec['name'],
            'description' => $spec['description'],
            'subtype' => 'CUSTOM',
            'customer_file_source' => 'USER_PROVIDED_ONLY',
        ]);

        if ($response === null || ! $response->successful() || ! $response->json('id')) {
            $error = $this->errorOf($response);
            Log::error("Meta audience '{$list}' could not be created: ".json_encode($error));

            return ['id' => null, 'created' => false, 'error' => $error];
        }

        $id = (string) $response->json('id');
        Setting::put($spec['setting'], $id);

        return ['id' => $id, 'created' => true, 'error' => null];
    }

    /**
     * Push hashed rows into an audience in batches. Meta reports how many it took
     * and samples of what it could not parse.
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array{ok: bool, received: int, invalid: int, batches: int, error: ?array}
     */
    public function upload(string $audienceId, array $rows): array
    {
        $received = 0;
        $invalid = 0;
        $batches = 0;

        foreach (array_chunk($rows, self::BATCH) as $chunk) {
            if ($batches > 0 && ! app()->runningUnitTests()) {
                usleep(300_000);
            }
            $batches++;

            $response = $this->post("{$audienceId}/users", [
                'payload' => ['schema' => MetaUserData::AUDIENCE_SCHEMA, 'data' => $chunk],
            ]);

            if ($response === null || ! $response->successful()) {
                $error = $this->errorOf($response);
                Log::error("Meta audience {$audienceId} upload failed on batch {$batches}: ".json_encode($error));

                return ['ok' => false, 'received' => $received, 'invalid' => $invalid, 'batches' => $batches, 'error' => $error];
            }

            $received += (int) $response->json('num_received', 0);
            $batchInvalid = (int) $response->json('num_invalid_entries', 0);
            $invalid += $batchInvalid;

            if ($batchInvalid > 0) {
                Log::warning("Meta audience {$audienceId}: {$batchInvalid} invalid entries in batch {$batches}: ".json_encode($response->json('invalid_entry_samples')));
            }
        }

        return ['ok' => true, 'received' => $received, 'invalid' => $invalid, 'batches' => $batches, 'error' => null];
    }

    /** One Graph POST with the token in the body. Null when the request itself failed. */
    private function post(string $path, array $body): ?\Illuminate\Http\Client\Response
    {
        $url = sprintf('https://graph.facebook.com/%s/%s', config('services.meta.api_version', 'v25.0'), $path);

        try {
            return Http::timeout(30)->acceptJson()->post($url, $body + ['access_token' => config('services.meta.access_token')]);
        } catch (\Throwable $e) {
            Log::error("Meta request to {$path} failed: ".$e->getMessage());

            return null;
        }
    }

    /** Meta's error object only - never the request, which carries the token. */
    private function errorOf(?\Illuminate\Http\Client\Response $response): array
    {
        if ($response === null) {
            return ['message' => 'request failed', 'code' => null, 'error_subcode' => null];
        }

        $error = (array) ($response->json('error') ?? []);

        return [
            'message' => $error['message'] ?? 'HTTP '.$response->status(),
            'code' => $error['code'] ?? null,
            'error_subcode' => $error['error_subcode'] ?? null,
        ];
    }
}
