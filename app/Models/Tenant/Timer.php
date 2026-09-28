<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Timer extends Model
{
    protected $fillable = [
        'user_id', 'task_id', 'project_id',
        'started_at', 'paused_at', 'elapsed_seconds', 'description',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'elapsed_seconds' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function getTotalSecondsAttribute(): int
    {
        $accumulated = $this->elapsed_seconds;
        if (!$this->paused_at) {
            $accumulated += now()->diffInSeconds($this->started_at);
        }

        return $accumulated;
    }
}
