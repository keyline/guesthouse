<?php

namespace App\Models\Concerns;

use App\Models\AdminActivityLog;

/**
 * Writes an immutable admin_activity_logs row on create/update/delete,
 * capturing who, what, and the exact before/after values.
 *
 * Models can opt out of noisy attributes via $auditExclude and restrict
 * which events are logged via $auditEvents (e.g. system-maintained tables
 * that should only log manual edits).
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model): void {
            $model->recordActivity('created');
        });

        static::updated(function ($model): void {
            $model->recordActivity('updated');
        });

        static::deleted(function ($model): void {
            $model->recordActivity('deleted');
        });
    }

    protected function recordActivity(string $action): void
    {
        if (! in_array($action, $this->auditEvents ?? ['created', 'updated', 'deleted'], true)) {
            return;
        }

        $excluded = array_merge(
            ['created_at', 'updated_at', 'password', 'remember_token'],
            $this->auditExclude ?? [],
        );

        $old = null;
        $new = null;

        if ($action === 'updated') {
            $changes = collect($this->getChanges())->except($excluded);

            if ($changes->isEmpty()) {
                return;
            }

            $new = $changes->all();
            $old = collect($this->getOriginal())->only($changes->keys())->all();
        } elseif ($action === 'created') {
            $new = collect($this->getAttributes())->except($excluded)->filter(fn ($value) => $value !== null)->all();
        } else {
            $old = collect($this->getAttributes())->except($excluded)->all();
        }

        AdminActivityLog::record($this, $action, $old, $new);
    }
}
