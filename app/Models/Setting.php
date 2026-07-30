<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    /**
     * Store the SMTP key encrypted at rest. The accessor tolerates legacy
     * plaintext values (returns them as-is) so upgrading is seamless — the
     * value becomes encrypted the next time settings are saved.
     */
    protected function smtpPassword(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (blank($value)) {
                    return $value;
                }
                try {
                    return Crypt::decryptString($value);
                } catch (\Throwable) {
                    return $value; // legacy plaintext
                }
            },
            set: fn (?string $value) => blank($value) ? $value : Crypt::encryptString($value),
        );
    }

    protected $fillable = [
        'site_name',
        'site_tagline',
        'site_description',
        'logo_path',
        'icon_path',
        'favicon_path',
        'business_name',
        'business_type',
        'business_registration_number',
        'business_license_number',
        'tax_name',
        'tax_id',
        'currency_code',
        'currency_symbol',
        'timezone',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'primary_email',
        'support_email',
        'reservations_email',
        'primary_phone',
        'support_phone',
        'reservations_phone',
        'website_url',
        'bank_account_holder',
        'bank_name',
        'bank_account_number',
        'bank_routing_number',
        'bank_swift_code',
        'bank_iban',
        'default_check_in_time',
        'default_check_out_time',
        'minimum_advance_booking_days',
        'maximum_advance_booking_days',
        'cancellation_policy_days',
        'cancellation_policy_description',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'notification_email_sender',
        'terms_and_conditions',
        'privacy_policy',
        'cancellation_policy',
        'refund_policy',
        'enable_guest_reviews',
        'enable_online_payment',
        'social_media_links',
        'meta_description',
        'meta_keywords',
        'admin_sidebar_color',
        'admin_primary_color',
        'admin_accent_color',
        'admin_sidebar_text_color',
    ];

    protected $casts = [
        'enable_guest_reviews' => 'boolean',
        'enable_online_payment' => 'boolean',
        'default_check_in_time' => 'integer',
        'default_check_out_time' => 'integer',
        'minimum_advance_booking_days' => 'integer',
        'maximum_advance_booking_days' => 'integer',
        'cancellation_policy_days' => 'integer',
    ];

    public static function get($key, $default = null)
    {
        $settings = self::first();
        if (!$settings) {
            return $default;
        }
        return $settings->$key ?? $default;
    }

    public static function set($key, $value)
    {
        $settings = self::first() ?? self::create();
        $settings->$key = $value;
        $settings->save();
        return $settings;
    }
}
