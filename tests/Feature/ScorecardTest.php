<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

it('captures a scorecard result into a new student lead and notifies n8n', function () {
    Http::fake();

    $this->postJson(route('taab.scorecard.store'), [
        'name' => 'Ada',
        'email' => 'Ada@Example.com',
        'score' => 72,
        'tier' => 'ready',
        'dimensions' => ['skills' => 80, 'time' => 90, 'setup' => 60, 'mindset' => 75, 'market' => 55],
        'hosting_blocked' => false,
    ])->assertOk()->assertJson(['ok' => true]);

    $lead = DB::table('students')->where('email', 'ada@example.com')->first();
    expect($lead)->not->toBeNull()
        ->and($lead->interest)->toBe('scorecard')
        ->and($lead->scorecard_tier)->toBe('ready')
        ->and((int) $lead->scorecard_score)->toBe(72);

    Http::assertSent(fn ($req) => $req['type'] === 'scorecard_result' && $req['tier'] === 'ready');
});

it('enriches an existing lead without reclassifying their source', function () {
    Http::fake();
    DB::table('students')->insert([
        'name' => 'Grace', 'email' => 'grace@example.com', 'interest' => 'masterclass',
        'source' => 'waitlist', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->postJson(route('taab.scorecard.store'), [
        'email' => 'grace@example.com', 'score' => 30, 'tier' => 'not_yet',
    ])->assertOk();

    $lead = DB::table('students')->where('email', 'grace@example.com')->first();
    expect(DB::table('students')->count())->toBe(1)
        ->and($lead->source)->toBe('waitlist')       // unchanged
        ->and($lead->interest)->toBe('masterclass')  // unchanged
        ->and($lead->scorecard_tier)->toBe('not_yet');
});

it('rejects an invalid submission', function () {
    Http::fake();

    $this->postJson(route('taab.scorecard.store'), [
        'email' => 'not-an-email', 'score' => 200, 'tier' => 'bogus',
    ])->assertStatus(422);

    expect(DB::table('students')->count())->toBe(0);
    Http::assertNothingSent();
});
