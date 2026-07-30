<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PropertyRoomType extends Model
{
    protected $fillable = ['property_id','room_type_id','status','display_name','description','max_adults','max_children','is_pet_friendly','extra_bed_available','max_extra_beds','extra_bed_charge_minor','extra_bed_charge_basis','sort_order'];

    protected function casts(): array
    {
        return ['max_adults'=>'integer','max_children'=>'integer','is_pet_friendly'=>'boolean','extra_bed_available'=>'boolean','max_extra_beds'=>'integer','extra_bed_charge_minor'=>'integer','sort_order'=>'integer'];
    }

    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function roomType(): BelongsTo { return $this->belongsTo(RoomType::class); }
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_room_type_amenities')
            ->withPivot(['availability_mode','is_free','fee_minor','charge_basis','details'])->withTimestamps();
    }

    public function displayName(): string { return $this->display_name ?: $this->roomType->name; }
}
