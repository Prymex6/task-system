<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }

    protected $fillable = [
        'project_id', 'task_status_id', 'milestone_id', 'parent_task_id',
        'created_by', 'title', 'description', 'priority', 'start_date',
        'due_date', 'estimated_hours', 'story_points', 'is_billable',
        'completed_at', 'order',
    ];

    protected $casts = [
        'is_billable' => 'boolean',
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    // ── Relacje ───────────────────────────────────────────────────────────

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }

    public function milestone()
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_assignees');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'task_followers');
    }

    public function labels()
    {
        return $this->belongsToMany(TaskLabel::class, 'task_label_pivot');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at');
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function checklists()
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('order');
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function dependencies()
    {
        return $this->hasMany(TaskDependency::class);
    }

    public function recurring()
    {
        return $this->hasOne(TaskRecurring::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->completed_at !== null || ($this->status && $this->status->is_closed);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isCompleted();
    }

    public function getTotalLoggedHoursAttribute(): float
    {
        return (float) $this->timeEntries()->sum('hours');
    }

    public function getChecklistProgressAttribute(): array
    {
        $items = $this->checklists()->with('items')->get()->flatMap->items;
        $total = $items->count();
        $done = $items->where('is_completed', true)->count();

        return ['total' => $total, 'done' => $done, 'percent' => $total > 0 ? round(($done / $total) * 100) : 0];
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('assignees', fn ($q) => $q->where('user_id', $user->id));
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())->whereNull('completed_at');
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }
}
