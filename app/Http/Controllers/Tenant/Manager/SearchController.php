<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * One box that looks through projects, tasks, clients, invoices, tickets and
 * the knowledge base at once.
 *
 * What somebody may see is decided in SearchService: a member gets results
 * from the projects they are on, an admin gets everything.
 */
class SearchController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $query = trim((string) $request->query('q', ''));

        return Inertia::render('Tenant/Manager/Search', [
            'query' => $query,
            'results' => $query === '' ? [] : SearchService::search($query, $user),
        ]);
    }
}
