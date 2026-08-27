<?php

use App\Models\Enrollment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

function dueInstallment(array $overrides = []): Enrollment
{
    return Enrollment::create(array_merge([
        'full_name' => 'Ada Builder',
        'email' => 'ada'.uniqid().'@example.com',
        'payment_reference' => 'TEST_'.uniqid(),
        'amount' => 42000,
        'amount_total' => 84000,
        'balance_due' => 42000,
        'plan_type' => 'installment',
        'second_payment_status' => 'pending',
        'second_payment_due_at' => now()->subDay(),
        'cohort' => 2,
        'status' => 'paid',
    ], $overrides));
}

// The URL generator takes its root at boot, so setting config('app.url') mid-test
// does nothing - the same reason production needs `config:cache` after changing
// APP_URL. forceRootUrl is how you move it once the app is already running.
beforeEach(function () {
    // forceRootUrl sets the host but NOT the scheme - the scheme still comes from
    // the console request built out of APP_URL. Both are forced here so the test
    // mirrors a correctly configured production box.
    URL::forceRootUrl('https://ajbuildai.com');
    URL::forceScheme('https');
    config(['accelerator.installment_grace_hours' => 24]);
});

afterEach(function () {
    URL::forceRootUrl(null);
    URL::forceScheme(null);
});

it('sends the pay link and stamps it', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $e = dueInstallment();

    $this->artisan('installments:process')->assertSuccessful();

    $e->refresh();
    expect($e->second_payment_status)->toBe('link_sent')
        ->and($e->installment_reminder_sent_at)->not->toBeNull();
});

it('leaves the row unstamped when n8n rejects the send, so the next run retries', function () {
    Http::fake(['*' => Http::response('boom', 500)]);
    $e = dueInstallment();

    $this->artisan('installments:process')->assertFailed();

    $e->refresh();
    expect($e->second_payment_status)->toBe('pending')
        ->and($e->installment_reminder_sent_at)->toBeNull();
});

it('refuses to send a localhost pay link and leaves the student pending', function () {
    // The exact production incident: APP_URL unset, so the signed link points at
    // localhost. Emailing it is worse than not sending - the signature is over the
    // whole URL, so it can never be repaired.
    URL::forceRootUrl('http://localhost');
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $e = dueInstallment();

    $this->artisan('installments:process')->assertFailed();

    Http::assertNothingSent();
    $e->refresh();
    expect($e->second_payment_status)->toBe('pending');
});

it('sends a link pointing at the configured app url', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    dueInstallment();

    $this->artisan('installments:process')->assertSuccessful();

    Http::assertSent(fn ($request) => str_starts_with($request['pay_url'], 'https://ajbuildai.com/installment/'));
});

it('never suspends a student who was never successfully sent a link', function () {
    Http::fake(['*' => Http::response('boom', 500)]);
    $e = dueInstallment(['second_payment_due_at' => now()->subMonth()]);

    $this->artisan('installments:process');

    expect($e->fresh()->access_suspended)->toBeFalse();
});

it('suspends only once the grace period has run from the send, not the due date', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    // Due long ago, but only told about it an hour ago - grace hasn't elapsed.
    $fresh = dueInstallment([
        'second_payment_due_at' => now()->subMonth(),
        'second_payment_status' => 'link_sent',
        'installment_reminder_sent_at' => now()->subHour(),
    ]);

    // Told two days ago - grace elapsed.
    $stale = dueInstallment([
        'second_payment_due_at' => now()->subMonth(),
        'second_payment_status' => 'link_sent',
        'installment_reminder_sent_at' => now()->subDays(2),
    ]);

    $this->artisan('installments:process')->assertSuccessful();

    expect($fresh->fresh()->access_suspended)->toBeFalse()
        ->and($stale->fresh()->access_suspended)->toBeTrue();
});

it('ignores a student who has already cleared the balance', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $e = dueInstallment(['second_payment_status' => 'paid', 'balance_due' => 0]);

    $this->artisan('installments:process')->assertSuccessful();

    Http::assertNothingSent();
    expect($e->fresh()->second_payment_status)->toBe('paid');
});

it('has a real public origin to fall back on when APP_URL is local', function () {
    // AppServiceProvider uses this whenever config('app.url') is localhost in a
    // console run. If it were ever set to something local the original bug returns,
    // silently, and only students would notice.
    $public = (string) config('app.public_url');
    $host = parse_url($public, PHP_URL_HOST);

    expect($public)->toStartWith('https://');
    expect(in_array($host, ['localhost', '127.0.0.1', '::1'], true))->toBeFalse();
});
