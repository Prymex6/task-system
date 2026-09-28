<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TaskLabel extends Model
{
    protected $fillable = ['name', 'color'];

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_label_pivot');
    }
}
