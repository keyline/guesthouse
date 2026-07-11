<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActivityLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'property_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function record(Model $subject, string $action, ?array $old, ?array $new): void
    {
        $user = auth()->user();

        static::query()->create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
            'subject_label' => static::labelFor($subject),
            'property_id' => $subject->getAttribute('property_id')
                ?? ($subject instanceof Property ? $subject->id : null),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : substr((string) request()->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }

    private static function labelFor(Model $subject): ?string
    {
        foreach (['name', 'booking_number', 'room_number', 'code', 'email'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if ($value) {
                return $subject instanceof Room ? 'Room '.$value : (string) $value;
            }
        }

        if ($subject instanceof DailyRate) {
            return $subject->date?->format('d M Y');
        }

        if ($subject instanceof RoomTypeInventory) {
            return $subject->date?->format('d M Y');
        }

        return null;
    }
}
