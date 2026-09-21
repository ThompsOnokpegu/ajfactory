<?php

use App\Models\Enrollment;
use App\Models\MasterclassRegistration;
use App\Support\MetaUserData;

/**
 * Meta matches on hashes of NORMALISED values, so every rule here is a real match
 * rate: an un-lowercased email or a phone with a "+" is a person Meta never finds.
 */
it('hashes a normalised email', function () {
    // sha256('ada@example.com') - computed independently with `php -r`.
    expect(MetaUserData::email(' Ada@Example.com '))->toBe('ada@example.com')
        ->and(MetaUserData::hash(MetaUserData::email(' Ada@Example.com ')))
        ->toBe('b5fc85e55755f9e0d030a10ab4429b6b2944855f9a0d60077fe832becbc41d72')
        ->and(MetaUserData::email('not an email'))->toBeNull()
        ->and(MetaUserData::hash(null))->toBeNull()
        ->and(MetaUserData::hash(''))->toBeNull();
});

it('normalises phones to digits with a country code', function (?string $raw, ?string $expected) {
    expect(MetaUserData::phone($raw))->toBe($expected);
})->with([
    'local Nigerian format' => ['08012345678', '2348012345678'],
    'international with plus and spaces' => ['+234 801 234 5678', '2348012345678'],
    '00 dialling prefix' => ['002348012345678', '2348012345678'],
    'already international' => ['2348012345678', '2348012345678'],
    'US number with punctuation' => ['+1 (650) 555-1212', '16505551212'],
    'empty' => ['', null],
    'null' => [null, null],
    'too short' => ['123', null],
]);

it('splits and cleans names', function () {
    expect(MetaUserData::splitName('Ada Builder'))->toBe(['ada', 'builder'])
        ->and(MetaUserData::splitName("Mary-Jane O'Neil"))->toBe(['maryjane', 'oneil'])
        ->and(MetaUserData::splitName('Ada'))->toBe(['ada', null])
        ->and(MetaUserData::splitName('  Chukwuemeka   Okafor Jr.  '))->toBe(['chukwuemeka', 'okaforjr'])
        ->and(MetaUserData::splitName(''))->toBe([null, null])
        ->and(MetaUserData::name('Adaeze'))->toBe('adaeze');
});

it('infers country from the currency first, then the dialling code', function () {
    expect(MetaUserData::country('NGN', null))->toBe('ng')
        ->and(MetaUserData::country('GHS', '2348012345678'))->toBe('gh')   // currency wins
        ->and(MetaUserData::country('USD', '2348012345678'))->toBe('ng')   // USD is ambiguous, phone decides
        ->and(MetaUserData::country('USD', '16505551212'))->toBe('us')
        ->and(MetaUserData::country('USD', null))->toBeNull()
        ->and(MetaUserData::country(null, '447700900123'))->toBe('gb')
        ->and(MetaUserData::country(null, '27821234567'))->toBe('za');
});

it('builds a person from an enrollment and from a registration', function () {
    $e = Enrollment::create([
        'full_name' => 'Ada Builder', 'email' => 'ADA@example.com', 'whatsapp' => '0801 234 5678',
        'payment_reference' => 'ACC_p1', 'amount' => 120000, 'currency' => 'NGN', 'status' => 'paid',
    ]);

    expect(MetaUserData::fromEnrollment($e))->toBe([
        'email' => 'ada@example.com', 'phone' => '2348012345678',
        'first_name' => 'ada', 'last_name' => 'builder', 'country' => 'ng',
    ]);

    $r = MasterclassRegistration::create([
        'first_name' => 'Tunde', 'last_name' => 'Bakare', 'email' => 'tunde@example.com',
        'whatsapp' => '+233 20 123 4567', 'session_date' => '2026-09-12',
    ]);

    expect(MetaUserData::fromRegistration($r))->toBe([
        'email' => 'tunde@example.com', 'phone' => '233201234567',
        'first_name' => 'tunde', 'last_name' => 'bakare', 'country' => 'gh',
    ]);
});

it('hashes identity fields for the Conversions API but passes browser signals through', function () {
    $userData = MetaUserData::capiUserData(
        ['email' => 'ada@example.com', 'phone' => '2348012345678', 'first_name' => 'ada', 'last_name' => null, 'country' => 'ng'],
        ['fbp' => 'fb.1.1.123', 'fbc' => null, 'ip' => '105.112.0.1', 'ua' => 'Mozilla/5.0'],
        'enrollment:7',
    );

    expect($userData)->toBe([
        'em' => hash('sha256', 'ada@example.com'),
        'ph' => hash('sha256', '2348012345678'),
        'fn' => hash('sha256', 'ada'),
        'country' => hash('sha256', 'ng'),
        'external_id' => hash('sha256', 'enrollment:7'),
        'client_ip_address' => '105.112.0.1',
        'client_user_agent' => 'Mozilla/5.0',
        'fbp' => 'fb.1.1.123',
    ]);
    // Nothing un-hashed that identifies the person, and no empty keys.
    expect($userData)->not->toHaveKeys(['ln', 'fbc'])
        ->and(implode(' ', $userData))->not->toContain('ada@example.com');
});

it('builds an audience row in schema order with empty strings for missing keys', function () {
    $row = MetaUserData::audienceRow(['email' => 'ada@example.com', 'first_name' => 'ada']);

    expect(MetaUserData::AUDIENCE_SCHEMA)->toBe(['EMAIL', 'PHONE', 'FN', 'LN', 'COUNTRY'])
        ->and($row)->toBe([hash('sha256', 'ada@example.com'), '', hash('sha256', 'ada'), '', '']);
});
