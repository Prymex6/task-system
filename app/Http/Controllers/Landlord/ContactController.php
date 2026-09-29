<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\ContactInquiry;
use Inertia\Inertia;

/**
 * Enquiries sent through the platform's public contact form.
 */
class ContactController extends Controller
{
    public function index()
    {
        return Inertia::render('Landlord/Contacts/Index', [
            'inquiries' => ContactInquiry::orderByDesc('created_at')->paginate(25),
            'unread' => ContactInquiry::where('read', false)->count(),
        ]);
    }

    public function markRead(ContactInquiry $inquiry)
    {
        $inquiry->update(['read' => true]);

        return back()->with('success', __('messages.marked_as_read'));
    }

    public function destroy(ContactInquiry $inquiry)
    {
        $inquiry->delete();

        return back()->with('success', __('messages.inquiry_deleted'));
    }
}
