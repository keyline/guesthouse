<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyRuleSet extends Model
{
    use LogsActivity;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = ['property_id', 'version', 'status', 'effective_from', 'effective_until', 'published_by', 'published_at'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'effective_from' => 'date', 'effective_until' => 'date', 'published_at' => 'datetime'];
    }

    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function publisher(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }
    public function rules(): HasMany { return $this->hasMany(PropertyRule::class)->orderBy('sort_order')->orderBy('id'); }

    public function auditPropertyId(): ?int { return $this->property_id; }
    public function auditLabel(): string { return ($this->property?->name ?? 'Property').' rules v'.$this->version; }

    public function snapshot(): array
    {
        $this->loadMissing('rules');

        return [
            'version' => $this->version,
            'published_at' => $this->published_at?->toIso8601String(),
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_until' => $this->effective_until?->toDateString(),
            'rules' => $this->rules->map(fn (PropertyRule $rule) => [
                'category' => $rule->category,
                'key' => $rule->rule_key,
                'label' => $rule->label,
                'selection' => $rule->selection,
                'message' => $rule->guest_message,
                'must_read' => $rule->is_must_read,
            ])->values()->all(),
        ];
    }
}
