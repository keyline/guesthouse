<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_logout(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('StrongPass123'),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'StrongPass123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_customer_account_can_register(): void
    {
        $this->post('/register', [
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ])->assertRedirect(route('customer.dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'guest@example.com',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
    }

    public function test_customer_cannot_login_to_admin_portal(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('StrongPass123'),
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->post('/admin/login', [
            'email' => $customer->email,
            'password' => 'StrongPass123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
