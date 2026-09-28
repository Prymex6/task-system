<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->action, fn ($q) => $q->where('action', 'like', "%{$request->action}%"))
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/Settings/AuditLog', [
            'logs' => $logs,
            'filters' => $request->only(['action', 'user_id']),
        ]);
    }
}
