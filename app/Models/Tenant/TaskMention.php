<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TaskMention extends Model
{
    protected $fillable = ['task_id', 'comment_id', 'mentioned_user_id', 'mentioned_by'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function mentionedUser()
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    public function mentionedBy()
    {
        return $this->belongsTo(User::class, 'mentioned_by');
    }
}
