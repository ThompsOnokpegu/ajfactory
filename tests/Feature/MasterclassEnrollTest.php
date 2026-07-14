<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function seedWaitlister(string $email, string $name = 'Ada Lovelace'): void
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

it('enrols waitlisters into the session and sends each a confirmation', function () {
    Http::fake();
    $session = config('taab.masterclass.date');

    seedWaitlister('ada@example.com', 'Ada Lovelace');
    seedWaitlister('grace@example.com', 'Grace');

    $this->artisan('masterclass:enroll-waitlist')->assertSuccessful();

    expect(DB::table('masterclass_registrations')->where('session_date', $session)->count())->toBe(2);

    $ada = DB::table('masterclass_registrations')->where('email', 'ada@example.com')->first();
    expect($ada->first_name)->toBe('Ada')
        ->and($ada->last_name)->toBe('Lovelace')
        ->and($ada->status)->toBe('registered');

    // Reclassified out of the waitlist.
    expect(DB::table('students')->where('email', 'ada@example.com')->value('source'))->toBe('registration');

    Http::assertSentCount(2);
    Http::assertSent(fn ($req) => $req['type'] === 'masterclass_registration' && $req['session_date'] === $session);
});

it('is idempotent — a second run enrols and notifies nobody', function () {
    Http::fake();
    seedWaitlister('ada@example.com');

    $this->artisan('masterclass:enroll-waitlist')->assertSuccessful();
    Http::assertSentCount(1);

    $this->artisan('masterclass:enroll-waitlist')->assertSuccessful();
    Http::assertSentCount(1); // unchanged
});

it('skips a lead already registered for the session', function () {
    Http::fake();
    $session = config('taab.masterclass.date');

    DB::table('masterclass_registrations')->insert([
        'first_name' => 'Al', 'last_name' => 'Ready', 'email' => 'al@example.com',
        'session_date' => $session, 'status' => 'registered',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    seedWaitlister('al@example.com', 'Al Ready');

    $this->artisan('masterclass:enroll-waitlist')->assertSuccessful();

    expect(DB::table('masterclass_registrations')->where('email', 'al@example.com')->count())->toBe(1);
    Http::assertSentCount(0);
});

it('writes nothing and sends nothing on a dry run', function () {
    Http::fake();
    seedWaitlister('ada@example.com');

    $this->artisan('masterclass:enroll-waitlist --dry-run')->assertSuccessful();

    expect(DB::table('masterclass_registrations')->count())->toBe(0);
    expect(DB::table('students')->where('email', 'ada@example.com')->value('source'))->toBe('waitlist');
    Http::assertNothingSent();
});
