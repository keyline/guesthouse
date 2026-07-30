<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table): void {
            $table->string('code', 80)->nullable()->unique()->after('name');
            $table->string('scope', 30)->default('multi_scope')->index()->after('type');
            $table->boolean('supports_fee')->default(false)->after('scope');
            $table->boolean('is_guest_visible')->default(true)->after('supports_fee');
        });

        $usedCodes = [];
        foreach (DB::table('amenities')->orderBy('id')->get() as $amenity) {
            $base = \Illuminate\Support\Str::slug($amenity->name) ?: 'amenity-'.$amenity->id;
            $code = $base; $number = 2;
            while (in_array($code, $usedCodes, true)) $code = $base.'-'.$number++;
            $usedCodes[] = $code;
            DB::table('amenities')->where('id', $amenity->id)->update(['code' => $code]);
        }

        Schema::create('property_room_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('max_adults')->default(2);
            $table->unsignedSmallInteger('max_children')->default(0);
            $table->boolean('is_pet_friendly')->default(false);
            $table->boolean('extra_bed_available')->default(false);
            $table->unsignedSmallInteger('max_extra_beds')->default(0);
            $table->unsignedInteger('extra_bed_charge_minor')->default(0);
            $table->string('extra_bed_charge_basis', 20)->default('per_night');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['property_id', 'room_type_id']);
        });

        Schema::create('property_room_type_amenities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->restrictOnDelete();
            $table->string('availability_mode', 20)->default('included');
            $table->boolean('is_free')->default(true);
            $table->unsignedInteger('fee_minor')->default(0);
            $table->string('charge_basis', 30)->nullable();
            $table->string('details', 500)->nullable();
            $table->timestamps();
            $table->unique(['property_room_type_id', 'amenity_id']);
        });

        Schema::create('room_amenity_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->restrictOnDelete();
            $table->string('state', 20);
            $table->string('reason', 500)->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->timestamps();
            $table->unique(['room_id', 'amenity_id']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->json('room_configuration_snapshot')->nullable()->after('cancellation_policy_snapshot');
        });

        $pairs = collect()
            ->merge(DB::table('rooms')->select('property_id', 'room_type_id')->get())
            ->merge(DB::table('rate_plans')->select('property_id', 'room_type_id')->get())
            ->merge(DB::table('bookings')->select('property_id', 'room_type_id')->get())
            ->unique(fn ($row) => $row->property_id.'|'.$row->room_type_id);

        foreach ($pairs as $pair) {
            $type = DB::table('room_types')->where('id', $pair->room_type_id)->first();
            if (! $type) continue;
            $configurationId = DB::table('property_room_types')->insertGetId([
                'property_id' => $pair->property_id, 'room_type_id' => $pair->room_type_id,
                'max_adults' => $type->max_adults, 'max_children' => $type->max_children,
                'is_pet_friendly' => $type->is_pet_friendly ?? false,
                'extra_bed_available' => $type->extra_bed_available ?? false,
                'max_extra_beds' => $type->max_extra_beds ?? 0,
                'extra_bed_charge_minor' => $type->extra_bed_charge_minor ?? 0,
                'extra_bed_charge_basis' => $type->extra_bed_charge_basis ?? 'per_night',
                'sort_order' => $type->sort_order ?? 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (DB::table('amenity_room_type')->where('room_type_id', $pair->room_type_id)->pluck('amenity_id') as $amenityId) {
                DB::table('property_room_type_amenities')->insert([
                    'property_room_type_id' => $configurationId, 'amenity_id' => $amenityId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('room_configuration_snapshot'));
        Schema::dropIfExists('room_amenity_overrides');
        Schema::dropIfExists('property_room_type_amenities');
        Schema::dropIfExists('property_room_types');
        Schema::table('amenities', fn (Blueprint $table) => $table->dropColumn(['code', 'scope', 'supports_fee', 'is_guest_visible']));
    }
};
