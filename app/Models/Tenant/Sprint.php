<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'name', 'goal', 'start_date', 'end_date', 'status', 'started_at', 'completed_at'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'sprint_tasks')->withPivot('story_points', 'order');
    }
}
