<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'icon',
        'category',
        'type',
        'is_active',
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class)->withTimestamps();
    }

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(RoomType::class, 'amenity_room_type')->withTimestamps();
    }

    public function banquets(): BelongsToMany
    {
        return $this->belongsToMany(Banquet::class, 'banquet_amenity')->withTimestamps();
    }
}
