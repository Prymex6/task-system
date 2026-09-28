<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TaskChecklistItem extends Model
{
    protected $fillable = ['task_checklist_id', 'assigned_to', 'content', 'is_completed', 'completed_at', 'order'];

    protected $casts = ['is_completed' => 'boolean', 'completed_at' => 'datetime'];

    public function checklist()
    {
        return $this->belongsTo(TaskChecklist::class, 'task_checklist_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
