<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->boolean('extra_bed_available')->default(false)->after('is_pet_friendly');
            $table->unsignedTinyInteger('max_extra_beds')->default(0)->after('extra_bed_available');
            $table->unsignedInteger('extra_bed_charge_minor')->default(0)->after('max_extra_beds');
            $table->string('extra_bed_charge_basis', 20)->default('per_night')->after('extra_bed_charge_minor');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', fn (Blueprint $table) => $table->dropColumn(['extra_bed_available','max_extra_beds','extra_bed_charge_minor','extra_bed_charge_basis']));
    }
};
