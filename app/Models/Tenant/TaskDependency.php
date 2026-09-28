<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TaskDependency extends Model
{
    protected $fillable = ['task_id', 'depends_on_id', 'type'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function dependsOn()
    {
        return $this->belongsTo(Task::class, 'depends_on_id');
    }
}
