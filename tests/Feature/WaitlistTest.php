<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

it('renders the builders waitlist page', function () {
    $this->get('/builders')->assertOk()->assertSee('The next cohort');
});

it('captures a waitlist signup into students + fires n8n', function () {
    config(['services.n8n.student_webhook_url' => 'https://example.test/hook']);
    Http::fake();

    Volt::test('student-waitlist')
        ->set('name', 'Ada Builder')
        ->set('email', 'Ada@Example.com')
        ->set('whatsapp', '+2348000000000')
        ->call('join')
        ->assertSet('joined', true);

    $row = DB::table('students')->where('email', 'ada@example.com')->first();
    expect($row)->not->toBeNull();
    expect($row->source)->toBe('accelerator_waitlist');
    expect($row->interest)->toBe('accelerator');

    Http::assertSent(fn ($r) => $r->url() === 'https://example.test/hook' && $r['type'] === 'student_signup');
});

it('does not duplicate a waitlist signup', function () {
    Http::fake();
    DB::table('students')->insert([
        'name' => 'Ada', 'email' => 'ada@example.com', 'interest' => 'accelerator',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    Volt::test('student-waitlist')
        ->set('name', 'Ada')
        ->set('email', 'ada@example.com')
        ->set('whatsapp', '+2348000000000')
        ->call('join')
        ->assertSet('joined', true);

    expect(DB::table('students')->where('email', 'ada@example.com')->count())->toBe(1);
});

it('validates required fields', function () {
    Volt::test('student-waitlist')
        ->set('name', '')
        ->set('email', 'not-an-email')
        ->set('whatsapp', '')
        ->call('join')
        ->assertHasErrors(['name', 'email', 'whatsapp']);

    expect(DB::table('students')->count())->toBe(0);
});
