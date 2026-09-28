<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TimesheetApproval extends Model
{
    protected $fillable = ['user_id', 'week_start', 'total_hours', 'status', 'approved_by', 'submitted_at', 'reviewed_at', 'rejection_reason'];

    protected $casts = [
        'week_start' => 'date:Y-m-d',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
