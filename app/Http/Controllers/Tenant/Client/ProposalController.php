<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Proposal;

class ProposalController extends Controller
{
    public function trackView(string $token)
    {
        $proposal = Proposal::where('view_token', $token)->firstOrFail();
        $proposal->increment('views_count');
        $proposal->update(['last_viewed_at' => now()]);

        return response()->noContent();
    }
}
