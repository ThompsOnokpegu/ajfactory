<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Pin the clock inside the registration window so /taab shows the registration
// form (not the waitlist card) regardless of when the suite runs. Without this,
// these tests break the moment the calendar passes registration_closes.
// Derived from config, not hardcoded: a literal date here goes stale (and starts
// testing the wrong branch) the moment the masterclass session rolls forward.
beforeEach(fn () => Carbon::setTestNow(
    Carbon::parse(config('taab.masterclass.registration_closes'), 'Africa/Lagos')->subDay()->setTime(10, 0)
));
afterEach(fn () => Carbon::setTestNow());

function seedInvite(string $email, string $token, ?string $name = null): void
{
    DB::table('masterclass_invites')->insert([
        'email' => $email,
        'name' => $name,
        'token' => $token,
        'session_date' => config('taab.masterclass.date'),
        'invited_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('renders a blank form with no token', function () {
    $this->get('/taab')
        ->assertOk()
        ->assertSee('name="first_name"', false);
});

it('ignores an unknown token and stays blank', function () {
    $res = $this->get('/taab?i=doesnotexist')->assertOk();

    // No value injected into the first-name field.
    expect($res->getContent())->toContain('id="fname"')
        ->and($res->getContent())->not->toContain('value="Ada"');
});

it('pre-fills identity + background from a past registration', function () {
    DB::table('masterclass_registrations')->insert([
        'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com',
        'whatsapp' => '+2348001112222', 'background' => 'Business owner / entrepreneur',
        'goal' => 'All of the above', 'session_date' => '2026-07-01', 'status' => 'registered',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    seedInvite('ada@example.com', 'tok-past-1');

    $html = $this->get('/taab?i=tok-past-1')->assertOk()->getContent();

    expect($html)->toContain('value="Ada"')
        ->and($html)->toContain('value="Lovelace"')
        ->and($html)->toContain('value="ada@example.com"')
        ->and($html)->toContain('value="+2348001112222"')
        // Background option is pre-selected...
        ->and($html)->toContain('<option selected>Business owner / entrepreneur')
        // ...but goal is deliberately NOT pre-filled (fresh signal).
        ->and($html)->not->toContain('selected>All of the above');
});

it('pre-fills identity only from a waitlist lead (no background)', function () {
    DB::table('students')->insert([
        'name' => 'Grace Hopper', 'email' => 'grace@example.com', 'whatsapp' => '+2348003334444',
        'interest' => 'masterclass', 'source' => 'waitlist',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    seedInvite('grace@example.com', 'tok-wait-1', 'Grace Hopper');

    $html = $this->get('/taab?i=tok-wait-1')->assertOk()->getContent();

    expect($html)->toContain('value="Grace"')
        ->and($html)->toContain('value="Hopper"')
        ->and($html)->toContain('value="grace@example.com"')
        // Background select falls back to the disabled placeholder.
        ->and($html)->toContain('disabled selected>Select one');
});
