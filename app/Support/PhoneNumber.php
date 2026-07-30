<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class PhoneNumber
{
    public static function normalize(string $countryCode, string $national): array
    {
        $code = '+'.ltrim(preg_replace('/\D+/', '', $countryCode), '0');
        $number = ltrim(preg_replace('/\D+/', '', $national), '0');

        if ($code === '+91' && ! preg_match('/^[6-9][0-9]{9}$/', $number)) {
            throw ValidationException::withMessages(['phone_national' => 'Enter a valid 10-digit Indian mobile number.']);
        }
        if ($code !== '+91' && (strlen($number) < 6 || strlen($number) > 14)) {
            throw ValidationException::withMessages(['phone_national' => 'Enter a valid international mobile number.']);
        }
        if (strlen(ltrim($code, '+').$number) > 15) {
            throw ValidationException::withMessages(['phone_national' => 'The complete number must not exceed 15 digits.']);
        }

        return ['country_code' => $code, 'national' => $number, 'e164' => $code.$number];
    }

    public static function countryCodes(): array
    {
        return ['+91' => '🇮🇳 India +91', '+1' => '🇺🇸/🇨🇦 +1', '+44' => '🇬🇧 UK +44', '+971' => '🇦🇪 UAE +971', '+65' => '🇸🇬 Singapore +65', '+61' => '🇦🇺 Australia +61', '+880' => '🇧🇩 Bangladesh +880', '+977' => '🇳🇵 Nepal +977', '+94' => '🇱🇰 Sri Lanka +94', '+49' => '🇩🇪 Germany +49', '+33' => '🇫🇷 France +33'];
    }
}
