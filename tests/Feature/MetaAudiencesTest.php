<?php

use App\Models\Enrollment;
use App\Models\MasterclassRegistration;
use App\Models\Setting;
use App\Support\MetaAudiences;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * meta:sync-audiences - customer lists to Meta as hashed Custom Audiences.
 * META_ACCESS_TOKEN / META_AD_ACCOUNT_ID are pinned in phpunit.xml (the command is a
 * no-op without them, which would otherwise pass locally and fail on CI).
 */
function paidBuyer(array $overrides = []): Enrollment
{
    static $n = 0;
    $n++;

    return Enrollment::create(array_merge([
        'full_name' => "Buyer {$n}", 'email' => "buyer{$n}@example.com", 'whatsapp' => '0801234567'.$n,
        'payment_reference' => "ACC_b{$n}", 'amount' => 120000, 'plan_type' => 'full', 'cohort' => 3,
        'currency' => 'NGN', 'status' => 'paid', 'paid_at' => now()->subDays(10)->addMinutes($n),
    ], $overrides));
}

function metaAudienceApi(): array
{
    return [
        'graph.facebook.com/*/act_*/customaudiences' => Http::response(['id' => '9001']),
        'graph.facebook.com/*/9001/users' => Http::response(['audience_id' => '9001', 'session_id' => 's', 'num_received' => 2, 'num_invalid_entries' => 0]),
    ];
}

function audienceUploads(): \Illuminate\Support\Collection
{
    return collect(Http::recorded(fn ($req) => str_ends_with($req->url(), '/users')))->map(fn ($pair) => $pair[0]);
}

it('creates the buyers audience on first run, stores its id, and uploads only paid buyers', function () {
    paidBuyer(['full_name' => 'Ada Builder', 'email' => 'ada@example.com', 'whatsapp' => '08012345678']);
    paidBuyer(['email' => 'tunde@example.com', 'currency' => 'GHS']);
    paidBuyer(['email' => 'abandoned@example.com', 'status' => 'pending']);
    Http::fake(metaAudienceApi());

    $this->artisan('meta:sync-audiences', ['--list' => 'buyers'])
        ->expectsOutputToContain('[buyers] 2 source rows -> 2 people')
        ->expectsOutputToContain('audience 9001 (created now)')
        ->expectsOutputToContain('uploaded 2 in 1 batch(es), 0 invalid')
        ->assertSuccessful();

    expect(Setting::get('meta_audience_buyers_id'))->toBe('9001')
        ->and(Setting::get('meta_audience_leads_id'))->toBeNull();

    Http::assertSent(fn ($r) => str_contains($r->url(), 'https://graph.facebook.com/v25.0/act_1234567890/customaudiences')
        && $r['subtype'] === 'CUSTOM'
        && $r['customer_file_source'] === 'USER_PROVIDED_ONLY'
        && $r['name'] === 'AJBuildAI - Accelerator buyers (app sync)'
        && $r['access_token'] === 'test-meta-token');

    $upload = audienceUploads()->sole();
    expect($upload['payload']['schema'])->toBe(['EMAIL', 'PHONE', 'FN', 'LN', 'COUNTRY'])
        ->and($upload['payload']['data'])->toHaveCount(2)
        ->and($upload['payload']['data'][0])->toBe([
            hash('sha256', 'ada@example.com'), hash('sha256', '2348012345678'),
            hash('sha256', 'ada'), hash('sha256', 'builder'), hash('sha256', 'ng'),
        ])
        ->and($upload['payload']['data'][1][4])->toBe(hash('sha256', 'gh'));

    // Only hashes leave the building.
    expect(json_encode($upload['payload']))->not->toContain('@example.com');
});

it('reuses an existing audience id from Setting and does not create another', function () {
    Setting::put('meta_audience_buyers_id', '777');
    paidBuyer();
    Http::fake(['graph.facebook.com/*/777/users' => Http::response(['num_received' => 1, 'num_invalid_entries' => 0])]);

    $this->artisan('meta:sync-audiences', ['--list' => 'buyers'])
        ->expectsOutputToContain('audience 777 (from Setting)')
        ->assertSuccessful();

    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'customaudiences'));
    Http::assertSentCount(1);
});

it('de-duplicates buyers by email, latest paid row wins', function () {
    paidBuyer(['email' => 'ada@example.com', 'whatsapp' => '08011111111', 'paid_at' => now()->subDays(30)]);
    paidBuyer(['email' => 'ADA@example.com', 'whatsapp' => '08022222222', 'paid_at' => now()->subDay()]);   // same person, re-enrolled
    Http::fake(metaAudienceApi());

    $this->artisan('meta:sync-audiences', ['--list' => 'buyers'])
        ->expectsOutputToContain('2 source rows -> 1 people (1 duplicate emails')
        ->assertSuccessful();

    $rows = audienceUploads()->sole()['payload']['data'];
    expect($rows)->toHaveCount(1)
        ->and($rows[0][1])->toBe(hash('sha256', '2348022222222'));
});

it('syncs masterclass registrants as the leads list', function () {
    MasterclassRegistration::create(['first_name' => 'Tunde', 'last_name' => 'Bakare', 'email' => 'tunde@example.com', 'whatsapp' => '+233201234567', 'session_date' => '2026-09-12']);
    MasterclassRegistration::create(['first_name' => 'Tunde', 'last_name' => 'Bakare', 'email' => 'tunde@example.com', 'whatsapp' => '+233201234567', 'session_date' => '2026-06-27']);   // two sessions, one person
    MasterclassRegistration::create(['first_name' => 'No', 'last_name' => 'Email', 'email' => 'not-an-email', 'session_date' => '2026-09-12']);
    Http::fake([
        'graph.facebook.com/*/act_*/customaudiences' => Http::response(['id' => '9002']),
        'graph.facebook.com/*/9002/users' => Http::response(['num_received' => 1, 'num_invalid_entries' => 0]),
    ]);

    $this->artisan('meta:sync-audiences', ['--list' => 'leads'])
        ->expectsOutputToContain('[leads] 3 source rows -> 1 people (1 duplicate emails, 1 without a valid email)')
        ->assertSuccessful();

    expect(Setting::get('meta_audience_leads_id'))->toBe('9002');
    Http::assertSent(fn ($r) => str_contains($r->url(), 'customaudiences') && $r['name'] === 'AJBuildAI - TAAB masterclass registrants (app sync)');

    $rows = audienceUploads()->sole()['payload']['data'];
    expect($rows[0])->toBe([
        hash('sha256', 'tunde@example.com'), hash('sha256', '233201234567'),
        hash('sha256', 'tunde'), hash('sha256', 'bakare'), hash('sha256', 'gh'),
    ]);
});

it('uploads in batches of 10,000', function () {
    $now = now();
    foreach (array_chunk(range(1, MetaAudiences::BATCH + 1), 500) as $chunk) {
        DB::table('masterclass_registrations')->insert(array_map(fn ($i) => [
            'first_name' => 'Lead', 'last_name' => (string) $i, 'email' => "lead{$i}@example.com",
            'session_date' => '2026-09-12', 'status' => 'registered', 'created_at' => $now, 'updated_at' => $now,
        ], $chunk));
    }
    Setting::put('meta_audience_leads_id', '9002');
    Http::fake(['graph.facebook.com/*/9002/users' => Http::response(['num_received' => 5000, 'num_invalid_entries' => 0])]);

    $this->artisan('meta:sync-audiences', ['--list' => 'leads'])
        ->expectsOutputToContain('in 2 batch(es)')
        ->assertSuccessful();

    $uploads = audienceUploads();
    expect($uploads)->toHaveCount(2)
        ->and($uploads[0]['payload']['data'])->toHaveCount(MetaAudiences::BATCH)
        ->and($uploads[1]['payload']['data'])->toHaveCount(1);
});

it('dry-run counts the lists, sends nothing, and stores nothing', function () {
    paidBuyer();
    Http::fake();

    $this->artisan('meta:sync-audiences', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] [buyers] 1 source rows -> 1 people')
        ->expectsOutputToContain('audience would be created: AJBuildAI - Accelerator buyers (app sync)')
        ->expectsOutputToContain('[dry-run] [leads] 0 source rows')
        ->expectsOutputToContain('Dry run - nothing sent.')
        ->assertSuccessful();

    Http::assertNothingSent();
    expect(Setting::get('meta_audience_buyers_id'))->toBeNull();
});

it('does nothing when Meta is not configured', function () {
    config(['services.meta.ad_account_id' => null]);
    paidBuyer();
    Http::fake();

    $this->artisan('meta:sync-audiences')
        ->expectsOutputToContain('not configured')
        ->assertSuccessful();

    Http::assertNothingSent();
});

it('names the fix when the Custom Audience Terms have not been accepted', function () {
    paidBuyer();
    Http::fake([
        'graph.facebook.com/*/act_*/customaudiences' => Http::response([
            'error' => ['message' => 'Custom Audience Terms Not Accepted', 'code' => 200, 'error_subcode' => 1870090],
        ], 400),
    ]);

    $this->artisan('meta:sync-audiences', ['--list' => 'buyers'])
        ->expectsOutputToContain('customaudiences/tos/?act=1234567890')
        ->assertFailed();

    expect(Setting::get('meta_audience_buyers_id'))->toBeNull();   // never store a half-made audience
    Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/users'));
});

it('fails the run when an upload is rejected, without touching the stored id', function () {
    Setting::put('meta_audience_buyers_id', '777');
    paidBuyer();
    Http::fake(['graph.facebook.com/*/777/users' => Http::response(['error' => ['message' => 'Invalid parameter', 'code' => 100]], 400)]);

    $this->artisan('meta:sync-audiences', ['--list' => 'buyers'])
        ->expectsOutputToContain('Meta rejected the request: Invalid parameter (code 100')
        ->assertFailed();

    expect(Setting::get('meta_audience_buyers_id'))->toBe('777');
});

it('rejects an unknown list name', function () {
    Http::fake();

    $this->artisan('meta:sync-audiences', ['--list' => 'buyers,vip'])
        ->expectsOutputToContain('Unknown list(s): vip')
        ->assertFailed();

    Http::assertNothingSent();
});
