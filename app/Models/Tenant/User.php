<?php

namespace App\Models\Tenant;

use App\Notifications\StaffResetPasswordNotification;
use Database\Factories\Tenant\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'workspace_role',
        'timezone',
        'language',
        'notification_preferences',
        'ui_preferences',
        'is_active',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'notification_preferences' => 'array',
        'ui_preferences' => 'array',
    ];

    // ── Relacje ───────────────────────────────────────────────────────────

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('project_role', 'added_by')
            ->withTimestamps();
    }

    public function assignedTasks()
    {
        return $this->belongsToMany(Task::class, 'task_assignees');
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function timer()
    {
        return $this->hasOne(Timer::class);
    }

    public function staffProfile()
    {
        return $this->hasOne(StaffProfile::class);
    }

    public function timesheets()
    {
        return $this->hasMany(TimesheetApproval::class);
    }

    // ── Role workspace ────────────────────────────────────────────────────

    public function isOwner(): bool
    {
        return $this->workspace_role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->workspace_role, ['owner', 'admin']);
    }

    public function isManager(): bool
    {
        return in_array($this->workspace_role, ['owner', 'admin', 'manager']);
    }

    public function isMember(): bool
    {
        return $this->workspace_role === 'member';
    }

    public function isGuest(): bool
    {
        return $this->workspace_role === 'guest';
    }

    /**
     * Whether this user holds a given workspace permission.
     * An owner or admin holds all of them, without a row to say so.
     */
    public function hasPermission(string $action): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $permission = RolePermission::where('role', $this->workspace_role)
            ->where('action', $action)
            ->first();

        return $permission ? (bool) $permission->allowed : false;
    }

    /**
     * Whether this user may open a given project.
     * Owner/Admin widzi wszystkie. Reszta tylko przypisane.
     */
    public function canAccessProject(Project $project): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $project->members()->where('user_id', $this->id)->exists();
    }

    /**
     * Their role on a project, or null when they are not on it.
     */
    public function projectRole(Project $project): ?string
    {
        if ($this->isAdmin()) {
            return 'project_manager';
        }

        $member = $project->members()->where('user_id', $this->id)->first();

        return $member?->pivot?->project_role;
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, string $role)
    {
        return $query->where('workspace_role', $role);
    }

    // ── Auth ──────────────────────────────────────────────────────────────

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new StaffResetPasswordNotification($token));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return null;
    }
}
