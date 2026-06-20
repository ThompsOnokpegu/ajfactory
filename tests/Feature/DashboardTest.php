<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_enrolled_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        Enrollment::create([
            'full_name' => $user->name,
            'email' => $user->email,
            'payment_reference' => 'T_' . uniqid(),
            'amount' => 79000,
            'status' => 'paid',
        ]);

        $this->actingAs($user);

        $this->get(route('dashboard'))->assertStatus(200);
    }

    public function test_unenrolled_users_are_sent_to_checkout(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertRedirect('/checkout');
    }
}
