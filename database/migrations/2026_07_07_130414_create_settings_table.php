<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // Brand & Site Information
            $table->string('site_name')->nullable();
            $table->string('site_tagline')->nullable();
            $table->text('site_description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();

            // Business Details
            $table->string('business_name')->nullable();
            $table->string('business_type')->default('hotel_chain'); // hotel_chain, guest_house, resort, etc.
            $table->string('business_registration_number')->nullable();
            $table->string('business_license_number')->nullable();

            // Tax & Financial Information
            $table->string('tax_name')->nullable(); // GST, VAT, IRS Number, etc.
            $table->string('tax_id')->nullable();
            $table->string('currency_code')->default('INR');
            $table->string('currency_symbol')->default('₹');
            $table->string('timezone')->default('Asia/Kolkata');

            // Business Address
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            // Contact Information
            $table->string('primary_email')->nullable();
            $table->string('support_email')->nullable();
            $table->string('reservations_email')->nullable();
            $table->string('primary_phone')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('reservations_phone')->nullable();
            $table->string('website_url')->nullable();

            // Bank Details
            $table->string('bank_account_holder')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_routing_number')->nullable();
            $table->string('bank_swift_code')->nullable();
            $table->string('bank_iban')->nullable();

            // Booking Settings
            $table->integer('default_check_in_time')->default(1200); // in minutes (14:00)
            $table->integer('default_check_out_time')->default(1100); // in minutes (11:00)
            $table->integer('minimum_advance_booking_days')->default(0);
            $table->integer('maximum_advance_booking_days')->default(365);
            $table->integer('cancellation_policy_days')->default(7);
            $table->text('cancellation_policy_description')->nullable();

            // Email Configuration
            $table->string('smtp_host')->nullable();
            $table->string('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->string('smtp_encryption')->nullable(); // tls, ssl
            $table->string('notification_email_sender')->nullable();

            // Legal & Policies
            $table->text('terms_and_conditions')->nullable();
            $table->text('privacy_policy')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->text('refund_policy')->nullable();

            // Additional Settings
            $table->boolean('enable_guest_reviews')->default(true);
            $table->boolean('enable_online_payment')->default(true);
            $table->text('social_media_links')->nullable(); // JSON
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
