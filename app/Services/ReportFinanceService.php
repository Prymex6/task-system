<?php

namespace App\Services;

use App\Models\Tenant\Expense;
use App\Models\Tenant\Invoice;
use Carbon\Carbon;

class ReportFinanceService
{
    public static function generate(array $filters): array
    {
        $from = $filters['from'] ?? now()->startOfYear()->toDateString();
        $to = $filters['to'] ?? today()->toDateString();

        $invoices = Invoice::whereBetween('issue_date', [$from, $to])
            ->with('client')
            ->get();

        $expenses = Expense::whereBetween('date', [$from, $to])->get();

        $revenue = $invoices->whereIn('status', ['paid', 'partial'])->sum('total');
        $pending = $invoices->whereIn('status', ['sent', 'draft'])->sum('total');
        $overdue = $invoices->where('status', 'overdue')->sum('total');
        $expTotal = $expenses->where('status', 'approved')->sum('amount');
        $profit = $revenue - $expTotal;

        $byMonth = $invoices->groupBy(fn ($i) => Carbon::parse($i->issue_date)->format('Y-m'));

        return [
            'revenue' => round($revenue, 2),
            'pending' => round($pending, 2),
            'overdue' => round($overdue, 2),
            'expenses' => round($expTotal, 2),
            'profit' => round($profit, 2),
            'profit_margin' => $revenue > 0 ? round($profit / $revenue * 100, 1) : 0,
            'by_month' => $byMonth->map(fn ($g, $m) => [
                'month' => $m,
                'invoiced' => round($g->sum('total'), 2),
                'paid' => round($g->where('status', 'paid')->sum('total'), 2),
            ])->values(),
            'by_client' => $invoices->groupBy('client_id')->map(fn ($g) => [
                'client' => $g->first()->client?->only(['id', 'name']),
                'invoiced' => round($g->sum('total'), 2),
                'paid' => round($g->where('status', 'paid')->sum('total'), 2),
            ])->sortByDesc('invoiced')->values()->take(10),
        ];
    }
}
