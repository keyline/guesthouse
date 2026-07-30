<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Corporate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_start_recognises_a_returning_guest(): void
    {
        User::factory()->create([
            'role' => User::ROLE_CUSTOMER, 'is_active' => true,
            'name' => 'Riya Sen', 'phone_e164' => '+919870012345',
        ]);

        $this->postJson(route('otp.start'), ['country_code' => '+91', 'mobile' => '98700 12345'])
            ->assertOk()
            ->assertJson(['exists' => true, 'first_name' => 'Riya', 'phone_e164' => '+919870012345'])
            ->assertJsonStructure(['dev_otp']);
    }

    public function test_otp_start_flags_a_new_number(): void
    {
        $this->postJson(route('otp.start'), ['country_code' => '+91', 'mobile' => '98700 12345'])
            ->assertOk()
            ->assertJson(['exists' => false]);
    }

    public function test_otp_is_not_exposed_when_on_screen_testing_is_disabled(): void
    {
        config()->set('services.booking_otp.show_on_screen', false);

        $this->postJson(route('otp.start'), ['country_code' => '+91', 'mobile' => '9870012345'])
            ->assertOk()
            ->assertJsonPath('dev_otp', null);
    }

    public function test_returning_guest_logs_in_with_correct_otp(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER, 'is_active' => true, 'phone_e164' => '+919870012345',
        ]);

        $otp = $this->postJson(route('otp.start'), ['country_code' => '+91', 'mobile' => '9870012345'])->json('dev_otp');

        $this->postJson(route('otp.verify'), ['country_code' => '+91', 'mobile' => '9870012345', 'otp' => $otp])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_otp_is_rejected(): void
    {
        User::factory()->create(['role' => User::ROLE_CUSTOMER, 'is_active' => true, 'phone_e164' => '+919870012345']);
        $this->postJson(route('otp.start'), ['country_code' => '+91', 'mobile' => '9870012345']);

        $this->postJson(route('otp.verify'), ['country_code' => '+91', 'mobile' => '9870012345', 'otp' => '000000'])
            ->assertStatus(422);

        $this->assertGuest();
    }

    public function test_new_guest_is_registered_after_otp_with_name(): void
    {
        $otp = $this->postJson(route('otp.start'), ['country_code' => '+91', 'mobile' => '9870099999'])->json('dev_otp');

        // Without a name the API asks for registration details.
        $this->postJson(route('otp.verify'), ['country_code' => '+91', 'mobile' => '9870099999', 'otp' => $otp])
            ->assertStatus(422)
            ->assertJson(['requires_name' => true]);

        $otp = $this->postJson(route('otp.start'), ['country_code' => '+91', 'mobile' => '9870099999'])->json('dev_otp');

        $this->postJson(route('otp.verify'), [
            'country_code' => '+91', 'mobile' => '9870099999', 'otp' => $otp,
            'name' => 'Arjun Das', 'email' => 'arjun@example.com',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('users', [
            'name' => 'Arjun Das',
            'email' => 'arjun@example.com',
            'phone_e164' => '+919870099999',
            'role' => User::ROLE_CUSTOMER,
        ]);
        $this->assertTrue(auth()->check());
    }

    public function test_booking_requires_a_signed_in_customer(): void
    {
        $this->post('/book', [
            'rooms' => [1 => 1],
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guest_name' => 'Anonymous', 'guest_phone' => '+91 98000 00000',
            'adults' => 1, 'children' => 0, 'payment_mode' => 'pay_at_property',
        ])->assertSessionHasErrors('auth');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_otp_attempts_are_limited(): void
    {
        User::factory()->create(['role' => User::ROLE_CUSTOMER, 'is_active' => true, 'phone_e164' => '+919870012345']);
        $this->postJson(route('otp.start'), ['country_code' => '+91', 'mobile' => '9870012345']);

        foreach (range(1, 5) as $ignored) {
            $this->postJson(route('otp.verify'), ['country_code' => '+91', 'mobile' => '9870012345', 'otp' => '000000']);
        }

        $this->postJson(route('otp.verify'), ['country_code' => '+91', 'mobile' => '9870012345', 'otp' => '000000'])
            ->assertStatus(429);
    }

    public function test_verified_mobile_can_complete_personal_registration(): void
    {
        $otp = $this->postJson(route('otp.start'), ['country_code' => '+91', 'mobile' => '9870011111'])->json('dev_otp');
        $this->postJson(route('otp.verify'), ['country_code' => '+91', 'mobile' => '9870011111', 'otp' => $otp, 'registration_flow' => true])
            ->assertOk()->assertJson(['requires_profile' => true]);

        $this->postJson(route('otp.register.complete'), [
            'customer_type' => 'individual', 'name' => 'Mira Roy', 'email' => 'mira@example.com',
            'address_line_1' => '12 Park Road', 'city' => 'Kolkata', 'state' => 'West Bengal',
            'postal_code' => '700001', 'country' => 'India', 'nationality' => 'Indian',
        ])->assertOk()->assertJson(['ok' => true, 'redirect' => route('customer.dashboard')]);

        $this->assertDatabaseHas('users', ['name' => 'Mira Roy', 'phone_e164' => '+919870011111', 'customer_type' => 'individual']);
        $this->assertAuthenticated();
    }

    public function test_verified_mobile_can_complete_corporate_registration(): void
    {
        $otp = $this->postJson(route('otp.start'), ['country_code' => '+91', 'mobile' => '9870022222'])->json('dev_otp');
        $this->postJson(route('otp.verify'), ['country_code' => '+91', 'mobile' => '9870022222', 'otp' => $otp, 'registration_flow' => true])->assertOk();

        $this->postJson(route('otp.register.complete'), [
            'customer_type' => 'corporate', 'name' => 'Amit Sen', 'email' => 'amit@acme.example',
            'legal_name' => 'Acme Hospitality Private Limited', 'trade_name' => 'Acme',
            'gstin' => '19ABCDE1234F1Z5', 'pan' => 'ABCDE1234F',
            'office_address_line_1' => '20 Business Park', 'office_city' => 'Kolkata',
            'office_state' => 'West Bengal', 'office_postal_code' => '700091', 'country' => 'India',
        ])->assertOk()->assertJson(['ok' => true]);

        $corporate = Corporate::query()->where('gstin', '19ABCDE1234F1Z5')->firstOrFail();
        $this->assertDatabaseHas('users', ['name' => 'Amit Sen', 'corporate_id' => $corporate->id, 'customer_type' => 'corporate']);
    }

    public function test_public_header_offers_registration_instead_of_book_now(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Login / Register')
            ->assertSee('data-register-open', false)
            ->assertSee(route('customer.login'), false);
    }

    public function test_public_registration_ui_displays_and_autofills_the_development_otp(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Development OTP:', false)
            ->assertSee('otpInput.value=data.dev_otp', false);

        $this->get('/book')
            ->assertOk()
            ->assertSee('Development OTP:', false)
            ->assertSee("el('#otpInput').value = data.dev_otp", false);
    }
}
