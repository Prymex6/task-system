<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PerformanceReview;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Performance reviews.
 *
 * Only an admin or a manager writes one, and nobody reviews themselves —
 * a review is a record of what somebody else thought, so signing your own
 * would make the whole file worthless.
 */
class PerformanceReviewController extends Controller
{
    public function index()
    {
        $user = $this->user();

        return Inertia::render('Tenant/Manager/HR/Performance/Index', [
            'reviews' => PerformanceReview::with(['user', 'reviewer'])
                ->when(!$this->canReview($user), fn ($q) => $q->where('user_id', $user->id))
                ->orderByDesc('created_at')
                ->paginate(25),
            'staff' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $reviewer = $this->reviewer();

        $validated = $request->validate($this->rules($reviewer));

        $review = PerformanceReview::create([
            ...$validated,
            'reviewed_by' => $reviewer->id,
        ]);

        AuditService::log('performance_review_created', $review, [], $review->only(['user_id', 'period', 'rating']));

        return back()->with('success', __('messages.review_saved'));
    }

    public function update(Request $request, PerformanceReview $performance)
    {
        $reviewer = $this->reviewer();

        $validated = $request->validate($this->rules($reviewer));

        $old = $performance->only(['rating', 'period']);
        $performance->update($validated);

        AuditService::log('performance_review_updated', $performance, $old, $performance->fresh()->only(['rating', 'period']));

        return back()->with('success', __('messages.review_updated'));
    }

    public function destroy(PerformanceReview $performance)
    {
        $this->reviewer();

        $performance->delete();

        AuditService::log('performance_review_deleted', $performance, ['period' => $performance->period], []);

        return back()->with('success', __('messages.review_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(User $reviewer): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id|different:' . $reviewer->id,
            'period' => 'required|string|max:50',
            'rating' => 'required|integer|min:1|max:5',
            'strengths' => 'nullable|string|max:2000',
            'improvements' => 'nullable|string|max:2000',
            'goals' => 'nullable|string|max:2000',
            'comments' => 'nullable|string|max:2000',
        ];
    }

    private function user(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        return $user;
    }

    private function canReview(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    private function reviewer(): User
    {
        $user = $this->user();
        abort_unless($this->canReview($user), 403);

        return $user;
    }
}
