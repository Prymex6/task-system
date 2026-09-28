<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProjectMilestone extends Model
{
    protected $fillable = ['project_id', 'name', 'description', 'due_date', 'is_completed', 'completed_at'];

    protected $casts = ['is_completed' => 'boolean', 'due_date' => 'date', 'completed_at' => 'datetime'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'milestone_id');
    }
}
