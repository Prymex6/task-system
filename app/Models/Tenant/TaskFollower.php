<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TaskFollower extends Model
{
    public $timestamps = false;

    protected $fillable = ['task_id', 'user_id'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
