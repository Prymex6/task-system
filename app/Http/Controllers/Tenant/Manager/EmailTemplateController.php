<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * The transactional e-mails a workspace can reword.
 *
 * `name` identifies which message the row overrides and is never editable,
 * so a template can always be matched back to the mailable that sends it.
 */
class EmailTemplateController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        return Inertia::render('Tenant/Manager/Settings/EmailTemplates', [
            'templates' => EmailTemplate::orderBy('label')->get(),
        ]);
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string|max:50000',
        ]);

        $this->assertKnownVariables($template, $validated['subject'] . $validated['body_html']);

        $template->update($validated);

        return back()->with('success', __('messages.template_saved'));
    }

    /**
     * Restore a template to the copy the seeder ships, for when an edit has
     * gone wrong and there is nothing to compare against.
     */
    public function reset(EmailTemplate $template)
    {
        $this->authorizeAdmin();

        // Template names contain dots ("invoice.sent"), which config() would
        // read as a nested path, so the whole map is fetched and indexed.
        $defaults = config('mail_templates')[$template->name] ?? null;

        if (!is_array($defaults)) {
            return back()->withErrors(['template' => __('messages.template_has_no_default')]);
        }

        $template->update([
            'subject' => $defaults['subject'],
            'body_html' => $defaults['body_html'],
        ]);

        return back()->with('success', __('messages.template_reset'));
    }

    /**
     * Placeholders are substituted by the mailable, so one that is not on the
     * template's list would reach the recipient as literal text.
     */
    private function assertKnownVariables(EmailTemplate $template, string $content): void
    {
        $allowed = $template->variables ?? [];

        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $content, $matches);

        $unknown = array_values(array_diff(array_unique($matches[1]), $allowed));

        if ($unknown !== []) {
            abort(422, __('messages.unknown_template_variables', ['names' => implode(', ', $unknown)]));
        }
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && $user->isAdmin(), 403);
    }
}
