<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\DealStage;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * The columns of the sales pipeline.
 *
 * A stage carries the odds a deal sitting in it eventually closes, which is
 * what the forecast is weighted by, and at most one of won or lost — a deal
 * cannot be both, and a stage that claimed to be would make the pipeline
 * total meaningless.
 */
class DealStageController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/DealStages', [
            'stages' => DealStage::withCount('deals')->orderBy('order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $stage = DealStage::create([
            ...$validated,
            'order' => (int) DealStage::max('order') + 1,
        ]);

        AuditService::log('deal_stage_created', $stage, [], $stage->only(['name', 'win_probability']));

        return back()->with('success', __('messages.stage_created'));
    }

    public function update(Request $request, DealStage $stage)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $old = $stage->only(['name', 'win_probability', 'is_won', 'is_lost']);
        $stage->update($validated);

        AuditService::log('deal_stage_updated', $stage, $old, $stage->fresh()->only(['name', 'win_probability', 'is_won', 'is_lost']));

        return back()->with('success', __('messages.stage_updated'));
    }

    public function destroy(DealStage $stage)
    {
        $this->admin();

        // Deleting a stage with deals on it would leave them outside the
        // pipeline, counted nowhere.
        if ($stage->deals()->exists()) {
            return back()->with('error', __('messages.stage_has_deals'));
        }

        $stage->delete();

        AuditService::log('deal_stage_deleted', $stage, ['name' => $stage->name], []);

        return back()->with('success', __('messages.stage_deleted'));
    }

    public function reorder(Request $request)
    {
        $this->admin();

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:deal_stages,id',
        ]);

        foreach ($validated['ids'] as $position => $id) {
            DealStage::where('id', $id)->update(['order' => $position]);
        }

        return back()->with('success', __('messages.stage_order_saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'color' => 'required|string|max:7',
            'win_probability' => 'required|numeric|min:0|max:100',
            'is_won' => 'boolean',
            // A stage closes the pipeline one way or the other, or neither.
            // Both at once would make the forecast count the same deal as
            // won and lost.
            'is_lost' => 'boolean|prohibited_if:is_won,1,true',
        ];
    }

    private function admin(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        return $user;
    }
}
