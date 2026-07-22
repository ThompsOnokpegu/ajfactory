<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

it('renders the earn capture page', function () {
    $this->get('/earn')->assertOk()->assertSee('proven', false);
});

it('captures a lead tagged interest=earn / source=clients and fires n8n', function () {
    config(['services.n8n.student_webhook_url' => 'https://example.test/hook']);
    Http::fake();

    Volt::test('client-leads')
        ->set('name', 'Ada Builder')
        ->set('email', 'Ada@Example.com')
        ->set('whatsapp', '+2348012345678')
        ->call('join')
        ->assertSet('joined', true);

    $lead = DB::table('students')->where('email', 'ada@example.com')->first();
    expect($lead)->not->toBeNull()
        ->and($lead->interest)->toBe('earn')
        ->and($lead->source)->toBe('clients');

    Http::assertSent(fn ($req) => $req['type'] === 'student_signup'
        && $req['source'] === 'clients'
        && $req['interest'] === 'earn'
        && $req['email'] === 'ada@example.com');
});

it('does not duplicate a lead on repeat submission', function () {
    Http::fake();
    DB::table('students')->insert([
        'name' => 'Ada', 'email' => 'ada@example.com', 'whatsapp' => '+2348012345678',
        'interest' => 'earn', 'source' => 'clients', 'created_at' => now(), 'updated_at' => now(),
    ]);

    Volt::test('client-leads')
        ->set('name', 'Ada Again')->set('email', 'ada@example.com')->set('whatsapp', '+2348012345678')
        ->call('join')->assertSet('joined', true);

    expect(DB::table('students')->where('email', 'ada@example.com')->count())->toBe(1);
    Http::assertNothingSent();
});

it('validates required fields', function () {
    Volt::test('client-leads')
        ->set('name', '')->set('email', 'nope')->set('whatsapp', '')
        ->call('join')
        ->assertHasErrors(['name', 'email', 'whatsapp'])
        ->assertSet('joined', false);
});
