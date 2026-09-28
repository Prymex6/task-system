<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['user_id', 'project_id', 'expense_category_id', 'amount', 'currency', 'description', 'receipt_path', 'expense_date', 'is_billable', 'is_approved', 'approved_by', 'approved_at'];

    protected $casts = ['expense_date' => 'date', 'is_billable' => 'boolean', 'is_approved' => 'boolean', 'approved_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
