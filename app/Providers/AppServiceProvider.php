<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applyMailSettings();
    }

    /**
     * Apply the SMTP settings saved in the admin panel (Settings → Email/SMTP)
     * over the default mail config, so outgoing mail uses the operator's
     * Brevo (or any SMTP) credentials without editing .env.
     */
    private function applyMailSettings(): void
    {
        // Guard against boot during migrations/install before the table exists.
        if (! Schema::hasTable('settings')) {
            return;
        }

        $settings = Setting::query()->first();
        if (! $settings || blank($settings->smtp_host)) {
            return;
        }

        $encryption = $settings->smtp_encryption === 'none' ? null : ($settings->smtp_encryption ?: 'tls');

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings->smtp_host,
            'mail.mailers.smtp.port' => (int) ($settings->smtp_port ?: 587),
            'mail.mailers.smtp.username' => $settings->smtp_username,
            'mail.mailers.smtp.password' => $settings->smtp_password,
            'mail.mailers.smtp.encryption' => $encryption,
        ]);

        if (filled($settings->notification_email_sender)) {
            config([
                'mail.from.address' => $settings->notification_email_sender,
                'mail.from.name' => $settings->site_name ?: $settings->business_name ?: config('app.name'),
            ]);
        }
    }
}
