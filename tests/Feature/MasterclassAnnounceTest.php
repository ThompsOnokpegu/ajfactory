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

/**
 * A lead captured by something other than the masterclass waitlist form. `interest`
 * is intentionally settable and defaults to something OTHER than 'masterclass' —
 * these rows are exactly what the old interest='masterclass' clause filtered out.
 */
function seedAnnounceLead(string $email, string $source, string $interest, string $name = 'Cold Lead'): void
{
    DB::table('students')->insert([
        'name' => $name,
        'email' => $email,
        'whatsapp' => '+2348000000001',
        'interest' => $interest,
        'source' => $source,
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

it('still invites someone who abandoned the checkout — a pending enrollment is not a buyer', function () {
    Http::fake();

    // The checkout writes a `pending` enrollment the moment the pay button is
    // clicked, before the payment modal opens. Treating that as a buyer excluded
    // every abandoned cart — our hottest segment — from every invite, silently.
    seedAnnounceWaitlister('bailed@example.com', 'Nearly Bought');
    DB::table('enrollments')->insert([
        'full_name' => 'Nearly Bought', 'email' => 'bailed@example.com',
        'payment_reference' => 'ACC_abandoned', 'amount' => 69000,
        'status' => 'pending',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->artisan('masterclass:announce')->assertSuccessful();

    expect(DB::table('masterclass_invites')->where('email', 'bailed@example.com')->count())->toBe(1);
    Http::assertSentCount(1);
});

it('suppresses a buyer even when an earlier abandoned attempt exists on the same email', function () {
    Http::fake();

    // Checkout uses Enrollment::create (not updateOrCreate), so someone who bailed
    // once and paid on the second attempt has BOTH rows. The paid one must win.
    seedAnnounceWaitlister('secondtime@example.com', 'Second Time');
    DB::table('enrollments')->insert([
        [
            'full_name' => 'Second Time', 'email' => 'secondtime@example.com',
            'payment_reference' => 'ACC_try1', 'amount' => 69000, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ],
        [
            'full_name' => 'Second Time', 'email' => 'secondtime@example.com',
            'payment_reference' => 'ACC_try2', 'amount' => 69000, 'status' => 'paid',
            'created_at' => now(), 'updated_at' => now(),
        ],
    ]);

    $this->artisan('masterclass:announce')->assertSuccessful();

    expect(DB::table('masterclass_invites')->count())->toBe(0);
    Http::assertNothingSent();
});

it('invites only source=waitlist by default, leaving the rest of the list alone', function () {
    Http::fake();

    seedAnnounceWaitlister('ada@example.com');
    seedAnnounceLead('cold@example.com', 'scorecard', 'scorecard');

    $this->artisan('masterclass:announce')->assertSuccessful();

    expect(DB::table('masterclass_invites')->pluck('email')->all())
        ->toBe(['ada@example.com']);
});

it('widens the audience to other capture sources with --sources', function () {
    Http::fake();

    // Each of these carries a DIFFERENT `interest` than 'masterclass' — the filter
    // this command used to apply. If that clause ever comes back, this fails.
    seedAnnounceWaitlister('ada@example.com');
    seedAnnounceLead('acc@example.com', 'accelerator_waitlist', 'accelerator');
    seedAnnounceLead('score@example.com', 'scorecard', 'scorecard');
    seedAnnounceLead('roi@example.com', 'roi', 'masterclass');
    seedAnnounceLead('ignored@example.com', 'clients', 'clients');

    $this->artisan('masterclass:announce --sources=waitlist,accelerator_waitlist,scorecard,roi')
        ->assertSuccessful();

    $invited = DB::table('masterclass_invites')->pluck('email')->all();
    expect($invited)->toHaveCount(4)
        ->toContain('ada@example.com', 'acc@example.com', 'score@example.com', 'roi@example.com')
        ->and($invited)->not->toContain('ignored@example.com');
});

it('stamps the real source on the ledger so pools can be reported on', function () {
    Http::fake();
    seedAnnounceLead('score@example.com', 'scorecard', 'scorecard');

    $this->artisan('masterclass:announce --sources=scorecard')->assertSuccessful();

    expect(DB::table('masterclass_invites')->where('email', 'score@example.com')->value('audience'))
        ->toBe('scorecard');
});

it('warns instead of silently matching nobody when a source is misspelled', function () {
    Http::fake();
    seedAnnounceLead('score@example.com', 'scorecard', 'scorecard');

    $this->artisan('masterclass:announce --sources=scorcard')   // typo
        ->expectsOutputToContain("Source 'scorcard' matches no rows")
        ->assertSuccessful();

    Http::assertNothingSent();
});

it('skips the student pool entirely on --sources=', function () {
    Http::fake();
    seedAnnounceWaitlister('ada@example.com');
    seedAnnounceRegistrant('grace@example.com', '2026-07-01');

    $this->artisan('masterclass:announce --sources=')->assertSuccessful();

    // Only the past-registrant pool remains.
    expect(DB::table('masterclass_invites')->pluck('email')->all())
        ->toBe(['grace@example.com']);
});

it('reaches NULL-source legacy leads via --interests', function () {
    Http::fake();

    // Every lead captured before the `source` column existed has source = NULL, and
    // whereIn('source', …) can never match NULL — so no value of --sources reaches
    // these. They are the OLDEST rows in the table and a large share of it.
    DB::table('students')->insert([
        ['name' => 'Old Lead', 'email' => 'legacy@example.com', 'whatsapp' => '08012345678',
         'interest' => 'course', 'source' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Community', 'email' => 'comm@example.com', 'whatsapp' => null,
         'interest' => 'community', 'source' => null, 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Not Wanted', 'email' => 'other@example.com', 'whatsapp' => null,
         'interest' => 'something-else', 'source' => null, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->artisan('masterclass:announce --sources= --interests=course,community')
        ->assertSuccessful();

    $invited = DB::table('masterclass_invites')->pluck('email')->all();
    expect($invited)->toHaveCount(2)
        ->toContain('legacy@example.com', 'comm@example.com')
        ->and($invited)->not->toContain('other@example.com');
});

it('ORs --sources and --interests rather than intersecting them', function () {
    Http::fake();

    seedAnnounceWaitlister('ada@example.com');                       // source only
    DB::table('students')->insert([                                   // interest only
        'name' => 'Old Lead', 'email' => 'legacy@example.com', 'whatsapp' => null,
        'interest' => 'course', 'source' => null,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->artisan('masterclass:announce --sources=waitlist --interests=course')
        ->assertSuccessful();

    expect(DB::table('masterclass_invites')->count())->toBe(2);
});

it('tags interest-matched leads on the ledger so the pool is reportable', function () {
    Http::fake();
    DB::table('students')->insert([
        'name' => 'Old Lead', 'email' => 'legacy@example.com', 'whatsapp' => null,
        'interest' => 'mentorship', 'source' => null,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->artisan('masterclass:announce --sources= --interests=mentorship')->assertSuccessful();

    expect(DB::table('masterclass_invites')->where('email', 'legacy@example.com')->value('audience'))
        ->toBe('interest:mentorship');
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
