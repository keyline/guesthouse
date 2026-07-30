<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyRule extends Model
{
    protected $fillable = ['property_rule_set_id', 'category', 'rule_key', 'label', 'selection', 'guest_message', 'is_must_read', 'sort_order'];

    protected function casts(): array { return ['is_must_read' => 'boolean', 'sort_order' => 'integer']; }
    public function ruleSet(): BelongsTo { return $this->belongsTo(PropertyRuleSet::class, 'property_rule_set_id'); }
}
