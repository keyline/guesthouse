<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Property;
use App\Models\PropertyRuleSet;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use App\Support\PropertyRuleCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PropertyRuleController extends Controller
{
    public function edit(Property $property, AdminPropertyScope $scope): View
    {
        $this->authorizeProperty($property, $scope);
        $draft = $this->draftFor($property);

        return view('admin.property-rules.edit', [
            'property' => $property,
            // Property rules are always edited in a fixed property context.
            // The top bar uses this value instead of offering the global
            // "All Properties" selector on this screen.
            'fixedPropertyContext' => $property,
            'draft' => $draft->load('rules'),
            'published' => $property->ruleSets()->where('status', PropertyRuleSet::STATUS_PUBLISHED)->with('rules')->latest('version')->first(),
            'catalog' => PropertyRuleCatalog::rules(),
            'categories' => PropertyRuleCatalog::categories(),
            'navItems' => AdminNavigation::make('properties'),
        ]);
    }

    public function update(Request $request, Property $property, AdminPropertyScope $scope): RedirectResponse
    {
        $this->authorizeProperty($property, $scope);
        $draft = $this->draftFor($property);
        $catalog = PropertyRuleCatalog::rules();
        $validated = $this->validated($request, $catalog);

        DB::transaction(function () use ($draft, $validated, $catalog): void {
            $draft->update([
                'effective_from' => $validated['effective_from'] ?? null,
                'effective_until' => $validated['effective_until'] ?? null,
            ]);
            $draft->rules()->delete();

            $position = 0;
            foreach ($catalog as $key => $definition) {
                $selection = $validated['rules'][$key]['selection'] ?? null;
                if (! $selection) {
                    continue;
                }

                $draft->rules()->create([
                    'category' => $definition['category'],
                    'rule_key' => $key,
                    'label' => $definition['label'],
                    'selection' => $selection,
                    'guest_message' => trim($validated['rules'][$key]['message'] ?? $definition['messages'][$selection]),
                    'is_must_read' => (bool) ($validated['rules'][$key]['must_read'] ?? $definition['must']),
                    'sort_order' => ++$position,
                ]);
            }

            $customMessages = collect(preg_split('/\R+/', trim((string) ($validated['custom_rules'] ?? ''))))
                ->map(fn ($message) => trim((string) $message, " \t\n\r\0\x0B•-"))
                ->filter()->take(10)->values();

            foreach ($customMessages as $index => $message) {
                $draft->rules()->create([
                    'category' => 'other', 'rule_key' => 'custom_'.($index + 1), 'label' => 'Other rule',
                    'selection' => 'info', 'guest_message' => $message, 'is_must_read' => false, 'sort_order' => ++$position,
                ]);
            }

            AdminActivityLog::record($draft, 'rules_saved', null, ['rule_count' => $position]);
        });

        return back()->with('status', 'Draft property rules saved. Review the guest preview, then publish.');
    }

    public function publish(Request $request, Property $property, AdminPropertyScope $scope): RedirectResponse
    {
        $this->authorizeProperty($property, $scope);
        $draft = $property->ruleSets()->where('status', PropertyRuleSet::STATUS_DRAFT)->with('rules')->latest('version')->firstOrFail();

        if ($draft->rules->isEmpty()) {
            return back()->withErrors(['rules' => 'Add at least one rule before publishing.']);
        }

        DB::transaction(function () use ($request, $property, $draft): void {
            // A future-dated version may coexist with today's live version.
            // For an immediate publication, archive versions it supersedes.
            if (! $draft->effective_from || $draft->effective_from->isToday() || $draft->effective_from->isPast()) {
                $property->ruleSets()->where('status', PropertyRuleSet::STATUS_PUBLISHED)->update(['status' => PropertyRuleSet::STATUS_ARCHIVED]);
            }
            $draft->update(['status' => PropertyRuleSet::STATUS_PUBLISHED, 'published_by' => $request->user()->id, 'published_at' => now()]);
            AdminActivityLog::record($draft, 'published', null, ['version' => $draft->version, 'rule_count' => $draft->rules->count()]);
        });

        return redirect()->route('admin.properties.rules.edit', $property)->with('status', 'Property rules version '.$draft->version.' published successfully.');
    }

    private function draftFor(Property $property): PropertyRuleSet
    {
        $existing = $property->ruleSets()->where('status', PropertyRuleSet::STATUS_DRAFT)->latest('version')->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($property): PropertyRuleSet {
            $published = $property->ruleSets()->where('status', PropertyRuleSet::STATUS_PUBLISHED)->with('rules')->latest('version')->lockForUpdate()->first();
            $nextVersion = ((int) $property->ruleSets()->max('version')) + 1;
            $draft = $property->ruleSets()->create([
                'version' => $nextVersion, 'status' => PropertyRuleSet::STATUS_DRAFT,
                'effective_from' => $published?->effective_from, 'effective_until' => $published?->effective_until,
            ]);

            $published?->rules->each(fn ($rule) => $draft->rules()->create($rule->only([
                'category', 'rule_key', 'label', 'selection', 'guest_message', 'is_must_read', 'sort_order',
            ])));

            return $draft;
        });
    }

    private function validated(Request $request, array $catalog): array
    {
        $rules = ['effective_from' => ['nullable','date'], 'effective_until' => ['nullable','date','after_or_equal:effective_from'], 'custom_rules' => ['nullable','string','max:3000']];
        foreach ($catalog as $key => $definition) {
            $rules["rules.$key.selection"] = ['nullable', Rule::in(array_keys($definition['options']))];
            $rules["rules.$key.message"] = ['nullable','string','max:500'];
            $rules["rules.$key.must_read"] = ['nullable','boolean'];
        }

        return $request->validate($rules);
    }

    private function authorizeProperty(Property $property, AdminPropertyScope $scope): void
    {
        abort_unless($scope->canAccessProperty($property->id), 404);
    }
}
