<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Corporate;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Mobile-OTP login for the public booking flow. The OTP is stored hashed in
 * cache for 5 minutes. Until an SMS gateway is configured, the code can be
 * returned to the UI using the temporary OTP_SHOW_ON_SCREEN setting.
 */
class OtpAuthController extends Controller
{
    private const TTL_SECONDS = 300;

    private const MAX_ATTEMPTS = 5;

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_code' => ['required', Rule::in(array_keys(PhoneNumber::countryCodes()))],
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        $phone = PhoneNumber::normalize($validated['country_code'], $validated['mobile']);
        $user = $this->customerByPhone($phone['e164']);

        $otp = (string) random_int(100000, 999999);
        Cache::put($this->key($phone['e164']), Hash::make($otp), self::TTL_SECONDS);
        Cache::put($this->key($phone['e164'], 'attempts'), 0, self::TTL_SECONDS);

        // No SMS gateway wired yet — log the code. Plug an SMS provider in here.
        Log::info('Booking OTP generated', ['phone' => $phone['e164']]);

        return response()->json([
            'exists' => (bool) $user,
            'first_name' => $user ? Str::before($user->name, ' ') : null,
            'phone_e164' => $phone['e164'],
            'dev_otp' => config('services.booking_otp.show_on_screen') ? $otp : null,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_code' => ['required', Rule::in(array_keys(PhoneNumber::countryCodes()))],
            'mobile' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:6'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'registration_flow' => ['nullable', 'boolean'],
        ]);

        $phone = PhoneNumber::normalize($validated['country_code'], $validated['mobile']);

        $attempts = (int) Cache::get($this->key($phone['e164'], 'attempts'), 0);
        if ($attempts >= self::MAX_ATTEMPTS) {
            return response()->json(['message' => 'Too many attempts — request a new code.'], 429);
        }
        Cache::put($this->key($phone['e164'], 'attempts'), $attempts + 1, self::TTL_SECONDS);

        $hash = Cache::get($this->key($phone['e164']));
        if (! $hash || ! Hash::check($validated['otp'], $hash)) {
            return response()->json(['message' => 'That code is incorrect or has expired.'], 422);
        }

        $user = $this->customerByPhone($phone['e164']);

        if (! $user && $request->boolean('registration_flow')) {
            Cache::forget($this->key($phone['e164']));
            Cache::forget($this->key($phone['e164'], 'attempts'));
            $request->session()->put('registration.verified_phone', $phone);

            return response()->json(['ok' => true, 'requires_profile' => true, 'phone' => $phone['e164'], 'csrf' => csrf_token()]);
        }

        if (! $user) {
            if (blank($validated['name'] ?? null)) {
                return response()->json(['message' => 'Please tell us your name to create your account.', 'requires_name' => true], 422);
            }

            $email = $validated['email'] ?? null;
            if ($email && User::query()->where('email', $email)->exists()) {
                return response()->json(['message' => 'This email already belongs to another account — leave it blank or sign in with password.'], 422);
            }

            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $email,
                'password' => Str::random(40),
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
                'customer_type' => 'individual',
                'phone' => $phone['e164'],
                'phone_country_code' => $phone['country_code'],
                'phone_national' => $phone['national'],
                'phone_e164' => $phone['e164'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'This account is disabled — please contact the property.'], 422);
        }

        Cache::forget($this->key($phone['e164']));
        Cache::forget($this->key($phone['e164'], 'attempts'));

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return response()->json([
            'ok' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_e164 ?: $user->phone,
            ],
            'csrf' => csrf_token(),
            'requires_profile' => false,
        ]);
    }

    public function completeRegistration(Request $request): JsonResponse
    {
        $phone = $request->session()->get('registration.verified_phone');
        if (! is_array($phone) || empty($phone['e164'])) {
            return response()->json(['message' => 'Mobile verification expired. Please verify your number again.'], 422);
        }

        $validated = $request->validate([
            'customer_type' => ['required', Rule::in(['individual', 'corporate'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male','female','other','not_specified'])],
            'nationality' => ['nullable', 'string', 'max:100'],
            'address_line_1' => ['nullable', 'required_if:customer_type,individual', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'required_if:customer_type,individual', 'string', 'max:100'],
            'state' => ['nullable', 'required_if:customer_type,individual', 'string', 'max:100'],
            'postal_code' => ['nullable', 'required_if:customer_type,individual', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'legal_name' => ['nullable', 'required_if:customer_type,corporate', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'gstin' => ['nullable', 'required_if:customer_type,corporate', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
            'pan' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'office_address_line_1' => ['nullable', 'required_if:customer_type,corporate', 'string', 'max:255'],
            'office_address_line_2' => ['nullable', 'string', 'max:255'],
            'office_city' => ['nullable', 'required_if:customer_type,corporate', 'string', 'max:100'],
            'office_state' => ['nullable', 'required_if:customer_type,corporate', 'string', 'max:100'],
            'office_postal_code' => ['nullable', 'required_if:customer_type,corporate', 'string', 'max:20'],
        ]);

        $user = DB::transaction(function () use ($validated, $phone): User {
            $corporate = null;
            if ($validated['customer_type'] === 'corporate') {
                $corporate = Corporate::query()->create([
                    'legal_name' => $validated['legal_name'], 'trade_name' => $validated['trade_name'] ?? null,
                    'gstin' => strtoupper($validated['gstin']), 'pan' => strtoupper($validated['pan'] ?? ''),
                    'contact_name' => $validated['name'], 'email' => $validated['email'] ?? null, 'phone' => $phone['e164'],
                    'address_line_1' => $validated['office_address_line_1'], 'address_line_2' => $validated['office_address_line_2'] ?? null,
                    'city' => $validated['office_city'], 'state' => $validated['office_state'], 'postal_code' => $validated['office_postal_code'],
                    'country' => $validated['country'], 'default_billing' => 'guest', 'is_active' => true,
                ]);
            }

            return User::query()->create([
                'name' => $validated['name'], 'email' => $validated['email'] ?? null, 'password' => Str::random(40),
                'role' => User::ROLE_CUSTOMER, 'is_active' => true, 'customer_type' => $validated['customer_type'], 'corporate_id' => $corporate?->id,
                'phone' => $phone['e164'], 'phone_country_code' => $phone['country_code'], 'phone_national' => $phone['national'], 'phone_e164' => $phone['e164'],
                'date_of_birth' => $validated['date_of_birth'] ?? null, 'gender' => $validated['gender'] ?? null,
                'nationality' => $validated['nationality'] ?? ($validated['country'] === 'India' ? 'Indian' : null),
                'address_line_1' => $validated['address_line_1'] ?? null, 'address_line_2' => $validated['address_line_2'] ?? null,
                'city' => $validated['city'] ?? null, 'state' => $validated['state'] ?? null, 'postal_code' => $validated['postal_code'] ?? null, 'country' => $validated['country'],
            ]);
        });

        $request->session()->forget('registration.verified_phone');
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return response()->json(['ok' => true, 'redirect' => route('customer.dashboard'), 'csrf' => csrf_token()]);
    }

    private function customerByPhone(string $e164): ?User
    {
        return User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->where(fn ($query) => $query->where('phone_e164', $e164)->orWhere('phone', $e164))
            ->first();
    }

    private function key(string $e164, string $suffix = 'code'): string
    {
        return "booking-otp:{$suffix}:{$e164}";
    }
}
