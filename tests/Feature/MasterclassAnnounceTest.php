<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

// These tests need "now" to sit inside the current edition's open registration
// window. DERIVE it from the shipped config — never hardcode a date. Pinning literal
// dates here broke the suite the first time the session rolled (2026-08-01 -> 2026-08-29),
// because "after the session" silently became "before the session".
beforeEach(function () {
    Carbon::setTestNow(openRegistrationMoment());
});

/** A moment while registration is still open for the configured session. */
function openRegistrationMoment(): Carbon
{
    return Carbon::parse(config('taab.masterclass.registration_closes'), 'Africa/Lagos')
        ->subDay()->setTime(10, 0);
}

/** A moment after registration has closed for the configured session. */
function closedRegistrationMoment(): Carbon
{
    return Carbon::parse(config('taab.masterclass.starts_at'), 'Africa/Lagos')->addHour();
}

afterEach(function () {
    Carbon::setTestNow();
});

function seedAnnounceWaitlister(string $email, string $name = 'Ada Lovelace'): void
{
    DB::table('students')->insert([
        'name' => $name,
        'email' => $email,
        'whatsapp' => '+2348000000000',
        'interest' => 'masterclass',
        'source' => 'waitlist',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function seedAnnounceRegistrant(string $email, string $sessionDate, string $first = 'Grace', string $last = 'Hopper'): void
{
    DB::table('masterclass_registrations')->insert([
        'first_name' => $first, 'last_name' => $last, 'email' => $email,
        'session_date' => $sessionDate, 'status' => 'registered',
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

it('invites waitlisters and recent past registrants to register', function () {
    Http::fake();
    $session = config('taab.masterclass.date');

    seedAnnounceWaitlister('ada@example.com', 'Ada Lovelace');
    seedAnnounceRegistrant('grace@example.com', '2026-07-01');
    seedAnnounceRegistrant('kwame@example.com', '2026-06-01');

    $this->artisan('masterclass:announce')->assertSuccessful();

    // Everyone stamped in the idempotency ledger for THIS session.
    expect(DB::table('masterclass_invites')->where('session_date', $session)->count())->toBe(3);
    Http::assertSentCount(3);
    Http::assertSent(fn ($req) => $req['type'] === 'masterclass_reinvite'
        && $req['session_date'] === $session
        && str_contains($req['register_url'], '/taab'));
});

it('stamps a token and carries it in the register link', function () {
    Http::fake();
    seedAnnounceWaitlister('ada@example.com');

    $this->artisan('masterclass:announce')->assertSuccessful();

    $token = DB::table('masterclass_invites')->where('email', 'ada@example.com')->value('token');
    expect($token)->not->toBeNull();
    // Must be a RELATIVE path — the email prepends https://ajbuildai.com, so an
    // absolute URL here would produce https://ajbuildai.comhttp://localhost/taab...
    Http::assertSent(fn ($req) => $req['register_url'] === "/taab?i={$token}");
});

it('suppresses people already registered for this session and Accelerator buyers', function () {
    Http::fake();
    $session = config('taab.masterclass.date');

    // Already registered for the upcoming session.
    seedAnnounceRegistrant('already@example.com', $session);
    seedAnnounceWaitlister('already@example.com', 'Already In');

    // A past registrant who has since bought the Accelerator.
    seedAnnounceRegistrant('buyer@example.com', '2026-07-01');
    DB::table('enrollments')->insert([
        'full_name' => 'Big Spender', 'email' => 'buyer@example.com',
        'payment_reference' => 'ref_test_123',
        'amount' => 100000, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->artisan('masterclass:announce')->assertSuccessful();

    expect(DB::table('masterclass_invites')->count())->toBe(0);
    Http::assertNothingSent();
});

it('is idempotent — a second run invites nobody new', function () {
    Http::fake();
    seedAnnounceWaitlister('ada@example.com');

    $this->artisan('masterclass:announce')->assertSuccessful();
    Http::assertSentCount(1);

    $this->artisan('masterclass:announce')->assertSuccessful();
    Http::assertSentCount(1); // unchanged
});

it('only reaches back --past-sessions sessions', function () {
    Http::fake();

    seedAnnounceRegistrant('s1@example.com', '2026-07-01'); // most recent past
    seedAnnounceRegistrant('s2@example.com', '2026-06-01'); // 2nd most recent
    seedAnnounceRegistrant('s3@example.com', '2026-05-01'); // 3rd — should be excluded at N=2

    $this->artisan('masterclass:announce --past-sessions=2')->assertSuccessful();

    $invited = DB::table('masterclass_invites')->pluck('email')->all();
    expect($invited)->toContain('s1@example.com', 's2@example.com')
        ->and($invited)->not->toContain('s3@example.com');
    Http::assertSentCount(2);
});

it('caps a run to --limit and sends the rest on the next run', function () {
    Http::fake();

    seedAnnounceWaitlister('a@example.com');
    seedAnnounceWaitlister('b@example.com');
    seedAnnounceWaitlister('c@example.com');

    // First run: only 2 go out and get stamped.
    $this->artisan('masterclass:announce --limit=2')->assertSuccessful();
    expect(DB::table('masterclass_invites')->count())->toBe(2);
    Http::assertSentCount(2);

    // Second run (simulating the next day): the held-back one goes.
    $this->artisan('masterclass:announce --limit=2')->assertSuccessful();
    expect(DB::table('masterclass_invites')->count())->toBe(3);
    Http::assertSentCount(3);
});

it('writes nothing and sends nothing on a dry run', function () {
    Http::fake();
    seedAnnounceWaitlister('ada@example.com');

    $this->artisan('masterclass:announce --dry-run')->assertSuccessful();

    expect(DB::table('masterclass_invites')->count())->toBe(0);
    Http::assertNothingSent();
});

it('does nothing when registration is closed', function () {
    Http::fake();
    Carbon::setTestNow(closedRegistrationMoment()); // after the session has started
    seedAnnounceWaitlister('ada@example.com');

    $this->artisan('masterclass:announce')->assertSuccessful();

    expect(DB::table('masterclass_invites')->count())->toBe(0);
    Http::assertNothingSent();
});
