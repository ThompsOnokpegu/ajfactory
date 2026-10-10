<?php

use App\Models\Checkpoint;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\Certificate;
use Livewire\Volt\Volt;

/*
 * Certificate of completion.
 *
 * The bar is every CORE checkpoint approved - not the completion guarantee, which also
 * wants live attendance. The code, once issued, must never change: it goes on CVs.
 */

function certCurriculum(int $modules = 3): void
{
    config(['curriculum' => [
        'core' => collect(range(1, $modules))->map(fn ($i) => [
            'id' => 'module-0' . $i,
            'title' => 'Module 0' . $i . ': Build ' . $i,
            'videos' => [['id' => "m{$i}v1", 'title' => 'V1', 'video_id' => 'x', 'duration' => '1:00']],
        ])->all(),
        'live' => [[
            'id' => 'live-01', 'title' => 'Live 1', 'release_at' => '2020-01-01 00:00:00',
            'videos' => [['id' => 'l1v1', 'title' => 'Rec', 'video_id' => 'y', 'duration' => '1:00']],
        ]],
    ]]);
}

function certStudent(string $name = 'Chidi Okonkwo', int $cohort = 3): Enrollment
{
    return Enrollment::create([
        'full_name' => $name,
        'email' => str($name)->slug() . '_' . uniqid() . '@test.dev',
        'payment_reference' => 'P_' . uniqid(),
        'amount' => 79000,
        'status' => 'paid',
        'cohort' => $cohort,
        'paid_at' => now(),
    ]);
}

function certApproveAll(Enrollment $e, int $modules = 3): void
{
    foreach (range(1, $modules) as $i) {
        Checkpoint::create([
            'enrollment_id' => $e->id,
            'module_id' => 'module-0' . $i,
            'status' => 'approved',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
        ]);
    }
}

function certLogin(Enrollment $e): User
{
    $u = User::factory()->create(['email' => $e->email, 'name' => $e->full_name]);
    test()->actingAs($u);

    return $u;
}

/* -------------------------------------------------------------- earning it -- */

it('is not earned with modules still outstanding', function () {
    certCurriculum();
    $e = certStudent();
    Checkpoint::create(['enrollment_id' => $e->id, 'module_id' => 'module-01', 'status' => 'approved',
        'submitted_at' => now()->subDay(), 'reviewed_at' => now()]);

    expect(Certificate::isEarnedBy($e->fresh()))->toBeFalse();
});

it('is earned once every core module is approved', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e);

    expect(Certificate::isEarnedBy($e->fresh()))->toBeTrue();
});

it('does not count a submitted-but-unapproved checkpoint', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e, 2);
    Checkpoint::create(['enrollment_id' => $e->id, 'module_id' => 'module-03', 'status' => 'submitted',
        'submitted_at' => now()]);

    expect(Certificate::isEarnedBy($e->fresh()))->toBeFalse();
});

it('ignores live-archive approvals when deciding', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e, 2);
    Checkpoint::create(['enrollment_id' => $e->id, 'module_id' => 'live-01', 'status' => 'approved',
        'submitted_at' => now()->subDay(), 'reviewed_at' => now()]);

    expect(Certificate::isEarnedBy($e->fresh()))->toBeFalse();
});

it('does not require live attendance, unlike the completion guarantee', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e);

    // No LiveAttendance rows at all.
    expect(Certificate::isEarnedBy($e->fresh()))->toBeTrue();
});

it('is never earned by legacy Cohort 1, which has no checkpoints', function () {
    certCurriculum();
    $e = certStudent('Legacy Student', 1);
    certApproveAll($e);

    expect(Certificate::isEarnedBy($e->fresh()))->toBeFalse();
});

/* ---------------------------------------------------------------- issuing -- */

it('issues a code and keeps it stable forever', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e);

    $first = Certificate::issueFor($e->fresh());
    $code = $first->certificate_code;
    $issued = $first->certificate_issued_at;

    expect($code)->toStartWith('AJ-');

    // Re-issuing must not mint a new code: the old one is on CVs and in screenshots.
    $again = Certificate::issueFor($first->fresh());
    expect($again->certificate_code)->toBe($code)
        ->and($again->certificate_issued_at->eq($issued))->toBeTrue();
});

it('issues nothing to a student who has not earned it', function () {
    certCurriculum();
    $e = certStudent();

    expect(Certificate::issueFor($e)->certificate_code)->toBeNull();
});

it('mints codes free of characters that misread in a screenshot', function () {
    certCurriculum();

    foreach (range(1, 15) as $i) {
        $e = certStudent("Student {$i}");
        certApproveAll($e);
        $code = Certificate::issueFor($e->fresh())->certificate_code;

        expect(substr($code, 3))->not->toMatch('/[OIL01AEU]/');
    }
});

/* ------------------------------------------------------------- the naming -- */

it('prints the enrolled name until the student chooses another', function () {
    $e = certStudent('Chidi Okonkwo');

    expect(Certificate::nameFor($e))->toBe('Chidi Okonkwo');

    $e->update(['certificate_name' => 'Chidi O. Okonkwo']);
    expect(Certificate::nameFor($e->fresh()))->toBe('Chidi O. Okonkwo');
});

it('lets a student set their printed name from the dashboard', function () {
    certCurriculum();
    $e = certStudent();
    certLogin($e);

    Volt::test('dashboard.terminal')
        ->set('certificateName', "Tolulope O. Ogunsola")
        ->call('saveCertificateName')
        ->assertHasNoErrors();

    expect($e->fresh()->certificate_name)->toBe('Tolulope O. Ogunsola');
});

it('never lets the printed name overwrite the name they paid under', function () {
    certCurriculum();
    $e = certStudent('Chidi Okonkwo');
    certLogin($e);

    Volt::test('dashboard.terminal')
        ->set('certificateName', 'Something Else')
        ->call('saveCertificateName');

    expect($e->fresh()->full_name)->toBe('Chidi Okonkwo');
});

it('rejects a printed name carrying a self-awarded title or junk', function () {
    certCurriculum();
    $e = certStudent();
    certLogin($e);

    foreach (['Chidi <b>Okonkwo</b>', 'Certified n8n Expert #1', 'A', str_repeat('x', 61)] as $bad) {
        Volt::test('dashboard.terminal')
            ->set('certificateName', $bad)
            ->call('saveCertificateName')
            ->assertHasErrors('certificateName');
    }

    expect($e->fresh()->certificate_name)->toBeNull();
});

it('accepts names with hyphens, apostrophes and accents', function () {
    certCurriculum();
    $e = certStudent();
    certLogin($e);

    foreach (["Ade-Bola N'Diaye", 'Zoë Akíntọ́lá', 'Chidi O. Okonkwo'] as $ok) {
        Volt::test('dashboard.terminal')
            ->set('certificateName', $ok)
            ->call('saveCertificateName')
            ->assertHasNoErrors();
    }
});

/* ---------------------------------------------------------------- the page -- */

it('issues the certificate as soon as the dashboard sees the last approval', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e);
    certLogin($e);

    Volt::test('dashboard.terminal')->assertSet('certificate.earned', true);

    expect($e->fresh()->certificate_code)->not->toBeNull();
});

it('shows progress toward the certificate before it is earned', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e, 2);
    certLogin($e);

    Volt::test('dashboard.terminal')
        ->assertSet('certificate.earned', false)
        ->assertSet('certificate.approved', 2)
        ->assertSet('certificate.total', 3);
});

it('serves the certificate page to a student who earned it', function () {
    certCurriculum();
    $e = certStudent('Chidi Okonkwo');
    certApproveAll($e);
    $e->update(['certificate_name' => 'Chidi O. Okonkwo']);
    certLogin($e);

    $this->get('/certificate')
        ->assertOk()
        ->assertSee('Certificate of Completion')
        ->assertSee('Chidi O. Okonkwo')
        // The module list deliberately does NOT appear here - it lives on the verify
        // page, so the certificate reads as a credential rather than a syllabus.
        ->assertDontSee('Module 01: Build 1')
        ->assertSee('Issued by: Deepr Web Services')
        ->assertSee('Verify:')
        // Says plainly what it is not. Deepr is not an awarding body, and implying
        // otherwise is the one way a certificate creates a real problem.
        ->assertSee('not an accredited qualification')
        ->assertDontSee('Certified Professional');
});

it('refuses the certificate page to a student who has not earned it', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e, 2);
    certLogin($e);

    $this->get('/certificate')->assertForbidden();
});

/* ------------------------------------------------------------ verification -- */

it('verifies a genuine code publicly, with no login', function () {
    certCurriculum();
    $e = certStudent('Chidi Okonkwo');
    certApproveAll($e);
    $code = Certificate::issueFor($e->fresh())->certificate_code;

    $this->get("/verify/{$code}")
        ->assertOk()
        ->assertSee('Verified credential')
        ->assertSee('Chidi Okonkwo')
        // The detail the certificate omits lives here - that split is the whole design.
        ->assertSee('Module 01: Build 1')
        ->assertSee('Deepr Web Services')
        ->assertDontSee($e->email);      // a verification tool, not a student directory
});

it('matches a code case-insensitively', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e);
    $code = Certificate::issueFor($e->fresh())->certificate_code;

    $this->get('/verify/' . strtolower($code))->assertOk()->assertSee('Verified credential');
});

it('reports no match for an unknown code', function () {
    certCurriculum();

    $this->get('/verify/AJ-ZZZZZZZZ')->assertOk()->assertSee('No match');
});

it('shows the plain form with no code submitted', function () {
    $this->get('/verify')->assertOk()->assertSee('Verify a certificate')->assertDontSee('No match');
});

it('does not verify a code that was never issued', function () {
    certCurriculum();
    $e = certStudent();
    // A code present without an issue date must not verify as a credential.
    $e->update(['certificate_code' => 'AJ-MANUAL22']);

    expect(Certificate::verify('AJ-MANUAL22'))->toBeNull();
    $this->get('/verify/AJ-MANUAL22')->assertSee('No match');
});

it('does not verify a certificate whose enrollment is not paid', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e);
    $code = Certificate::issueFor($e->fresh())->certificate_code;

    $e->update(['status' => 'refunded']);

    expect(Certificate::verify($code))->toBeNull();
});

/* ------------------------------------------------------------ bulk issuing -- */

it('issues in bulk to everyone who already qualified', function () {
    certCurriculum();

    $done = certStudent('Finished One');
    certApproveAll($done);
    $partial = certStudent('Halfway There');
    certApproveAll($partial, 2);

    $this->artisan('certificates:issue')->assertSuccessful();

    expect($done->fresh()->certificate_code)->not->toBeNull()
        ->and($partial->fresh()->certificate_code)->toBeNull();
});

it('writes nothing on a dry run', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e);

    $this->artisan('certificates:issue', ['--dry-run' => true])->assertSuccessful();

    expect($e->fresh()->certificate_code)->toBeNull();
});

it('is safe to run twice and never re-mints a code', function () {
    certCurriculum();
    $e = certStudent();
    certApproveAll($e);

    $this->artisan('certificates:issue')->assertSuccessful();
    $code = $e->fresh()->certificate_code;

    $this->artisan('certificates:issue')->assertSuccessful();

    expect($e->fresh()->certificate_code)->toBe($code);
});

it('can be limited to one cohort', function () {
    certCurriculum();

    $c2 = certStudent('Old Grad', 2);
    certApproveAll($c2);
    $c3 = certStudent('New Grad', 3);
    certApproveAll($c3);

    $this->artisan('certificates:issue', ['--cohort' => 2])->assertSuccessful();

    expect($c2->fresh()->certificate_code)->not->toBeNull()
        ->and($c3->fresh()->certificate_code)->toBeNull();
});

it('shows the certificate and the chosen name on the admin progress screen', function () {
    certCurriculum();
    $e = certStudent('Chidi Okonkwo', 3);
    certApproveAll($e);
    $e->update(['certificate_name' => 'Chidi O. Okonkwo']);
    $code = Certificate::issueFor($e->fresh())->certificate_code;

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Volt::test('admin.progress')->set('cohort', '3')
        ->assertSee($code)
        ->assertSee('Chidi O. Okonkwo');
});

/* ------------------------------------------------------------- previewing -- */

it('lets an admin preview the certificate without being a student', function () {
    certCurriculum();
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get('/admin/certificate/preview')
        ->assertOk()
        ->assertSee('Certificate of Completion')
        ->assertSee('Amara Nwachukwu')
        ->assertSee('Issued by: Deepr Web Services')
        // The module list lives on the verify page, not the certificate.
        ->assertDontSee('Module 01: Build 1');
});

it('lets an admin preview the verification page in its verified state', function () {
    certCurriculum();
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get('/admin/certificate/preview/verify')
        ->assertOk()
        ->assertSee('Verified credential')
        ->assertSee('Amara Nwachukwu')
        ->assertSee('Module 01: Build 1');
});

it('keeps both previews behind the admin gate', function () {
    certCurriculum();
    $e = certStudent();
    certLogin($e);

    $this->get('/admin/certificate/preview')->assertForbidden();
    $this->get('/admin/certificate/preview/verify')->assertForbidden();
});

it('uses a preview code that can never resolve as a real certificate', function () {
    certCurriculum();

    // Not a code mintCode() can produce, so it cannot collide with an issued one.
    expect(Certificate::verify('AJ-PREVIEW'))->toBeNull();

    $this->get('/verify/AJ-PREVIEW')->assertOk()->assertSee('No match');
});
