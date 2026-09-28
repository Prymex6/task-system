<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TaskRecurring extends Model
{
    protected $fillable = ['task_id', 'frequency', 'interval', 'next_occurrence', 'ends_at'];

    protected $casts = ['next_occurrence' => 'date', 'ends_at' => 'date'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
