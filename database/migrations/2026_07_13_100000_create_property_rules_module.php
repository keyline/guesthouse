<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_rule_sets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 20)->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'version']);
            $table->index(['property_id', 'status', 'effective_from']);
        });

        Schema::create('property_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_rule_set_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40);
            $table->string('rule_key', 80);
            $table->string('label', 160);
            $table->string('selection', 30);
            $table->text('guest_message');
            $table->boolean('is_must_read')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['property_rule_set_id', 'rule_key']);
            $table->index(['property_rule_set_id', 'category', 'sort_order']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->json('property_rules_snapshot')->nullable()->after('room_configuration_snapshot');
            $table->unsignedInteger('property_rules_version')->nullable()->after('property_rules_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['property_rules_snapshot', 'property_rules_version']);
        });
        Schema::dropIfExists('property_rules');
        Schema::dropIfExists('property_rule_sets');
    }
};
