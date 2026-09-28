<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Extra fields workspaces bolt onto projects, tasks, clients and invoices.
 *
 * `name` is the machine key the values table joins on, so it is derived once
 * from the label and then left alone — renaming it would orphan stored values.
 */
class CustomFieldController extends Controller
{
    private const CHOICE_TYPES = ['select', 'multiselect'];

    public function index()
    {
        $this->authorizeAdmin();

        return Inertia::render('Tenant/Manager/Settings/CustomFields', [
            'fields' => CustomField::orderBy('model')->orderBy('order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'model' => 'required|in:project,task,client,lead,invoice',
            'label' => 'required|string|max:100',
            'type' => 'required|in:text,number,date,select,multiselect,checkbox,textarea',
            'options' => 'nullable|array',
            'options.*' => 'string|max:100',
            'is_required' => 'boolean',
        ]);

        CustomField::create([
            ...$validated,
            'name' => $this->uniqueName($validated['label'], $validated['model']),
            'options' => in_array($validated['type'], self::CHOICE_TYPES, true)
                ? array_values($validated['options'] ?? [])
                : null,
            'order' => (int) CustomField::where('model', $validated['model'])->max('order') + 1,
        ]);

        return back()->with('success', __('messages.field_created'));
    }

    public function update(Request $request, CustomField $field)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'options' => 'nullable|array',
            'options.*' => 'string|max:100',
            'is_required' => 'boolean',
            'order' => 'sometimes|integer|min:0',
        ]);

        // The type is fixed after creation: stored values are already in its shape.
        $field->update([
            ...$validated,
            'options' => in_array($field->type, self::CHOICE_TYPES, true)
                ? array_values($validated['options'] ?? [])
                : null,
        ]);

        return back()->with('success', __('messages.field_updated'));
    }

    public function destroy(CustomField $field)
    {
        $this->authorizeAdmin();

        $field->delete();

        return back()->with('success', __('messages.field_deleted'));
    }

    private function uniqueName(string $label, string $model): string
    {
        $base = Str::slug($label, '_') ?: 'field';
        $name = $base;
        $i = 2;

        while (CustomField::where('model', $model)->where('name', $name)->exists()) {
            $name = $base . '_' . $i++;
        }

        return $name;
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && $user->isAdmin(), 403);
    }
}
