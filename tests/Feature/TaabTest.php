<?php

use App\Models\MasterclassRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Config for an open session (far-future close + start), so register() passes the gate. */
function openSession(array $extra = []): void
{
    config(array_merge([
        'taab.masterclass.date' => '2026-06-27',
        'taab.masterclass.timezone' => 'UTC',
        'taab.masterclass.registration_closes' => '2099-12-31',
        'taab.masterclass.starts_at' => '2099-12-31 09:00',
    ], $extra));
}

it('renders the three TAAB tool pages', function () {
    $this->get('/taab/scorecard')->assertOk()->assertSee('Readiness Scorecard');
    $this->get('/taab/roi-calculator')->assertOk()->assertSee('ROI Calculator');
    $this->get('/taab/tool-stack')->assertOk()->assertSee('Tool Stack Guide');
});

it('captures a TAAB lead into students and notifies n8n', function () {
    config(['services.n8n.student_webhook_url' => 'https://example.test/hook']);
    Http::fake();

    $this->postJson('/taab/lead', [
        'name' => 'Ada Builder',
        'email' => 'Ada@Example.com',
        'whatsapp' => '+2348000000000',
        'source' => 'scorecard',
    ])->assertOk()->assertJson(['ok' => true]);

    expect(DB::table('students')->where('email', 'ada@example.com')->exists())->toBeTrue();
    $row = DB::table('students')->where('email', 'ada@example.com')->first();
    expect($row->source)->toBe('scorecard');
    expect($row->interest)->toBe('masterclass');

    Http::assertSent(fn ($req) => $req->url() === 'https://example.test/hook'
        && $req['type'] === 'taab_lead'
        && $req['source'] === 'scorecard');
});

it('does not duplicate a lead on repeat submission', function () {
    Http::fake();

    $payload = ['name' => 'Ada', 'email' => 'ada@example.com', 'source' => 'roi'];
    $this->postJson('/taab/lead', $payload)->assertOk();
    $this->postJson('/taab/lead', array_merge($payload, ['name' => 'Ada Updated']))->assertOk();

    expect(DB::table('students')->where('email', 'ada@example.com')->count())->toBe(1);
    expect(DB::table('students')->where('email', 'ada@example.com')->value('name'))->toBe('Ada Updated');
});

it('captures a lead without a whatsapp number', function () {
    Http::fake();
    $this->postJson('/taab/lead', ['name' => 'Ada', 'email' => 'ada@example.com', 'source' => 'tool-stack'])
        ->assertOk();
    expect(DB::table('students')->where('email', 'ada@example.com')->value('whatsapp'))->toBeNull();
});

it('renders the TAAB hub with the configured session date', function () {
    config(['taab.masterclass.date' => '2026-06-27']);
    $this->get('/taab')->assertOk()
        ->assertSee('The AI Automation Bootcamp')
        ->assertSee('Saturday 27 June');
});

it('registers an attendee for the masterclass', function () {
    openSession(['services.n8n.student_webhook_url' => 'https://example.test/hook']);
    Http::fake();

    $this->postJson('/taab/register', [
        'first_name' => 'Adebayo',
        'last_name' => 'Okafor',
        'email' => 'Adebayo@Example.com',
        'whatsapp' => '+2348000000000',
        'background' => 'Freelancer / consultant',
        'goal' => 'All of the above',
    ])->assertOk()->assertJson(['ok' => true]);

    $reg = DB::table('masterclass_registrations')->where('email', 'adebayo@example.com')->first();
    expect($reg)->not->toBeNull();
    expect($reg->session_date)->toBe('2026-06-27');
    expect($reg->goal)->toBe('All of the above');

    // mirrored into the students waitlist
    expect(DB::table('students')->where('email', 'adebayo@example.com')->value('source'))->toBe('registration');

    Http::assertSent(fn ($req) => $req->url() === 'https://example.test/hook'
        && $req['type'] === 'masterclass_registration'
        && $req['session_date'] === '2026-06-27');
});

it('does not duplicate a registration for the same session', function () {
    openSession();
    Http::fake();
    $payload = ['first_name' => 'Ada', 'last_name' => 'O', 'email' => 'ada@example.com'];

    $this->postJson('/taab/register', $payload)->assertOk();
    $this->postJson('/taab/register', $payload)->assertOk();

    expect(DB::table('masterclass_registrations')->where('email', 'ada@example.com')->count())->toBe(1);
});

it('rejects an invalid registration', function () {
    openSession();
    $this->postJson('/taab/register', ['first_name' => 'Ada', 'email' => 'nope', 'last_name' => 'O'])
        ->assertStatus(422);
    expect(DB::table('masterclass_registrations')->count())->toBe(0);
});

it('rejects an invalid lead', function () {
    $this->postJson('/taab/lead', ['name' => 'Ada', 'email' => 'not-an-email', 'source' => 'scorecard'])
        ->assertStatus(422);
    $this->postJson('/taab/lead', ['name' => 'Ada', 'email' => 'ada@example.com', 'source' => 'bogus'])
        ->assertStatus(422);

    expect(DB::table('students')->count())->toBe(0);
});

/** Config for a session whose registration window has already closed. */
function closedSession(): void
{
    config([
        'taab.masterclass.date' => '2026-06-27',
        'taab.masterclass.timezone' => 'UTC',
        'taab.masterclass.registration_closes' => '2020-01-01',
        'taab.masterclass.starts_at' => '2020-01-02 09:00',
    ]);
}

it('rejects registration once the session has closed', function () {
    closedSession();

    $this->postJson('/taab/register', [
        'first_name' => 'Ada', 'last_name' => 'O', 'email' => 'ada@example.com',
    ])->assertStatus(422)->assertJson(['ok' => false]);

    expect(DB::table('masterclass_registrations')->count())->toBe(0);
});

it('shows the waitlist card when registration is closed', function () {
    closedSession();
    $this->get('/taab')->assertOk()->assertSee('Registration is closed');
});

it('captures a waitlist lead into students and notifies n8n', function () {
    config(['services.n8n.student_webhook_url' => 'https://example.test/hook']);
    Http::fake();

    $this->postJson('/taab/waitlist', [
        'name' => 'Ada Builder',
        'email' => 'Ada@Example.com',
        'whatsapp' => '+2348000000000',
    ])->assertOk()->assertJson(['ok' => true]);

    $row = DB::table('students')->where('email', 'ada@example.com')->first();
    expect($row)->not->toBeNull();
    expect($row->source)->toBe('waitlist');
    expect($row->interest)->toBe('masterclass');

    Http::assertSent(fn ($req) => $req->url() === 'https://example.test/hook'
        && $req['type'] === 'masterclass_waitlist'
        && $req['email'] === 'ada@example.com');
});

it('fires the masterclass reminder once and is idempotent', function () {
    config([
        'taab.masterclass.date' => '2026-06-27',
        'taab.masterclass.timezone' => 'UTC',
        'taab.masterclass.starts_at' => now()->addHours(23)->format('Y-m-d H:i'),
        'taab.masterclass.ends_at' => now()->addHours(25)->format('Y-m-d H:i'),
        'taab.masterclass.meet_url' => 'https://meet.example/abc',
        'taab.masterclass.whatsapp_group_url' => 'https://chat.whatsapp.example/xyz',
        'services.n8n.student_webhook_url' => 'https://example.test/hook',
    ]);
    Http::fake();

    $reg = MasterclassRegistration::create([
        'first_name' => 'Ada', 'last_name' => 'O', 'email' => 'ada@example.com',
        'session_date' => '2026-06-27', 'status' => 'registered',
    ]);

    $this->artisan('masterclass:remind')->assertExitCode(0);

    expect($reg->fresh()->reminder_sent_at)->not->toBeNull();
    expect($reg->fresh()->dayof_sent_at)->toBeNull();   // session is 23h out, day-of (T-2h) not yet
    Http::assertSent(fn ($req) => $req['type'] === 'masterclass_reminder'
        && $req['meet_url'] === 'https://meet.example/abc'
        && $req['whatsapp_group_url'] === 'https://chat.whatsapp.example/xyz');
    Http::assertSentCount(1);

    // Re-running must not send a second reminder.
    $this->artisan('masterclass:remind')->assertExitCode(0);
    Http::assertSentCount(1);
});

it('fires the post-session follow-up after the session ends', function () {
    config([
        'taab.masterclass.date' => '2026-06-27',
        'taab.masterclass.timezone' => 'UTC',
        'taab.masterclass.starts_at' => now()->subHours(4)->format('Y-m-d H:i'),
        'taab.masterclass.ends_at' => now()->subHours(3)->format('Y-m-d H:i'),
        'taab.masterclass.followup_after_hours' => 2,
        'services.n8n.student_webhook_url' => 'https://example.test/hook',
    ]);
    Http::fake();

    $reg = MasterclassRegistration::create([
        'first_name' => 'Ada', 'last_name' => 'O', 'email' => 'ada@example.com',
        'session_date' => '2026-06-27', 'status' => 'registered',
    ]);

    $this->artisan('masterclass:remind')->assertExitCode(0);

    expect($reg->fresh()->followup_sent_at)->not->toBeNull();
    expect($reg->fresh()->reminder_sent_at)->toBeNull(); // session already passed — no reminder
    Http::assertSent(fn ($req) => $req['type'] === 'masterclass_followup');
});
