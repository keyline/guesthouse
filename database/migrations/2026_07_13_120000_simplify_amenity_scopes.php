<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Collapse amenity "scope" from four values to two:
 *   property, room_category.
 *
 * - multi_scope  → property        (shared facilities like Wi-Fi live once on the property)
 * - physical_room → room_category  (per-room differences are handled as overrides on the category)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('amenities')->where('scope', 'multi_scope')->update(['scope' => 'property']);
        DB::table('amenities')->where('scope', 'physical_room')->update(['scope' => 'room_category']);
    }

    public function down(): void
    {
        // One-way data consolidation; the original per-row scope cannot be
        // reconstructed, so leave the simplified values in place.
    }
};
