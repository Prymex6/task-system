<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ExpenseReport extends Model
{
    protected $fillable = [
        'user_id', 'title', 'period_start', 'period_end', 'total_amount',
        'status', 'approved_by', 'submitted_at', 'reviewed_at', 'rejection_reason',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_amount' => 'decimal:2',
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

    /**
     * The expenses the report covers: everything the claimant filed inside
     * the period. Expenses are not tagged with a report id, so the period is
     * what ties them together.
     */
    public function expenses()
    {
        return Expense::where('user_id', $this->user_id)
            ->whereBetween('expense_date', [$this->period_start, $this->period_end]);
    }
}
