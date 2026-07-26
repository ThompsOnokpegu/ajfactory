<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

// Config ships with the current session = 2026-08-01, registration_closes
// 2026-07-31. Freeze "now" inside that open window so registrationOpen() is true.
beforeEach(function () {
    Carbon::setTestNow('2026-07-20 10:00:00');
});

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
    Http::assertSent(fn ($req) => str_contains($req['register_url'], 'i=' . $token));
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

it('writes nothing and sends nothing on a dry run', function () {
    Http::fake();
    seedAnnounceWaitlister('ada@example.com');

    $this->artisan('masterclass:announce --dry-run')->assertSuccessful();

    expect(DB::table('masterclass_invites')->count())->toBe(0);
    Http::assertNothingSent();
});

it('does nothing when registration is closed', function () {
    Http::fake();
    Carbon::setTestNow('2026-08-05 10:00:00'); // after the session
    seedAnnounceWaitlister('ada@example.com');

    $this->artisan('masterclass:announce')->assertSuccessful();

    expect(DB::table('masterclass_invites')->count())->toBe(0);
    Http::assertNothingSent();
});
