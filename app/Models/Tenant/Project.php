<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }

    protected $fillable = [
        'client_id', 'project_status_id', 'status', 'name', 'description', 'color',
        'budget', 'currency', 'start_date', 'deadline', 'due_date', 'visibility',
        'is_archived', 'created_by',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'start_date' => 'date',
        'deadline' => 'date',
        'due_date' => 'date',
        'budget' => 'decimal:2',
    ];

    // ── Relacje ───────────────────────────────────────────────────────────

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function status()
    {
        return $this->belongsTo(ProjectStatus::class, 'project_status_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('project_role', 'added_by', 'notify_on_task_create', 'notify_on_comment')
            ->withTimestamps();
    }

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function discussions()
    {
        return $this->hasMany(ProjectDiscussion::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function sprints()
    {
        return $this->hasMany(Sprint::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function budgetEntries()
    {
        return $this->hasMany(BudgetEntry::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope — zwraca projekty widoczne dla danego użytkownika.
     * Owner/Admin widzą wszystkie. Reszta tylko przypisane.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('members', fn ($q) => $q->where('user_id', $user->id));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getProgressAttribute(): int
    {
        $total = $this->tasks()->count();
        $completed = $this->tasks()->whereHas('status', fn ($q) => $q->where('is_closed', true))->count();

        return $total > 0 ? (int) round(($completed / $total) * 100) : 0;
    }

    public function getTotalLoggedHoursAttribute(): float
    {
        return (float) $this->timeEntries()->sum('hours');
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }
}
