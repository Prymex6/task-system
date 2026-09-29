<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TaskRecurring extends Model
{
    /**
     * The table is singular, and Eloquent would otherwise look for
     * task_recurrings and fail on every query.
     */
    protected $table = 'task_recurring';

    protected $fillable = ['task_id', 'frequency', 'interval', 'next_occurrence', 'ends_at'];

    protected $casts = [
        'interval' => 'integer',
        'next_occurrence' => 'date',
        'ends_at' => 'date',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
