<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('property_room_types as prt')
            ->join('amenity_property as ap', 'ap.property_id', '=', 'prt.property_id')
            ->join('amenities as a', 'a.id', '=', 'ap.amenity_id')
            ->whereIn('a.scope', ['room_category', 'multi_scope'])
            ->select('prt.id as property_room_type_id', 'a.id as amenity_id')->get();

        foreach ($rows as $row) {
            DB::table('property_room_type_amenities')->updateOrInsert(
                ['property_room_type_id'=>$row->property_room_type_id,'amenity_id'=>$row->amenity_id],
                ['availability_mode'=>'included','is_free'=>true,'fee_minor'=>0,'created_at'=>now(),'updated_at'=>now()]
            );
        }
    }

    public function down(): void
    {
        // Deliberately retain reviewed category configuration on rollback.
    }
};
