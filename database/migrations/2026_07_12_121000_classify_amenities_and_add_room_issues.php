<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('amenities')->whereIn('code', ['parking','restaurant','room-service','power-backup','security','lift','laundry','banquet-hall','swimming-pool'])->update(['scope'=>'property']);
        DB::table('amenities')->whereIn('code', ['air-conditioning','tv'])->update(['scope'=>'room_category']);
        DB::table('amenities')->where('code', 'wifi')->update(['scope'=>'multi_scope']);

        Schema::create('room_operational_issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('issue_type', 40)->default('amenity_unavailable');
            $table->string('description', 1000);
            $table->string('severity', 20)->default('warning');
            $table->boolean('blocks_sale')->default(false);
            $table->boolean('blocks_assignment')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expected_resolution_at')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();
            $table->index(['room_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_operational_issues');
        DB::table('amenities')->update(['scope'=>'multi_scope']);
    }
};
