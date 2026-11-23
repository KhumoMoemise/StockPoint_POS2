<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_cashiers_are_redirected_to_the_pos()
    {
        $user = User::factory()->create(['role' => User::ROLE_CASHIER]);
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertRedirect(route('cashier.dashboard', absolute: false));
    }

    public function test_authenticated_admins_are_redirected_to_the_admin_dashboard()
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }
}
