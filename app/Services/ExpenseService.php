<?php

namespace App\Services;

use App\Models\Tenant\Expense;
use App\Models\Tenant\User;

class ExpenseService
{
    public static function create(array $data, User $creator): Expense
    {
        $expense = Expense::create(array_merge($data, ['created_by' => $creator->id, 'status' => 'pending']));
        AuditService::log('expense.created', $expense);

        return $expense;
    }

    public static function approve(Expense $expense, User $approver): void
    {
        $expense->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
        AuditService::log('expense.approved', $expense);
    }

    public static function reject(Expense $expense, User $approver, string $reason = ''): void
    {
        $expense->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'notes' => $reason,
        ]);
        AuditService::log('expense.rejected', $expense);
    }

    public static function getSummary(array $filters = []): array
    {
        $query = Expense::query();

        if (!empty($filters['from'])) {
            $query->where('date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('date', '<=', $filters['to']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('expense_category_id', $filters['category_id']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('created_by', $filters['user_id']);
        }

        return [
            'total' => $query->clone()->sum('amount'),
            'approved' => $query->clone()->where('status', 'approved')->sum('amount'),
            'pending' => $query->clone()->where('status', 'pending')->sum('amount'),
        ];
    }
}
