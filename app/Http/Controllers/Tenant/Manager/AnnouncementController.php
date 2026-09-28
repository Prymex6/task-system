<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $announcements = Announcement::latest()->paginate(20);

        return Inertia::render('Tenant/Manager/HR/Announcements', ['announcements' => $announcements]);
    }

    public function create()
    {
        return redirect()->route('tenant.manager.announcements.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target' => 'nullable|string|max:50',
            'is_important' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        Announcement::create(array_merge($data, [
            'created_by' => Auth::guard('tenant')->id(),
            'target' => $data['target'] ?? 'all',
        ]));

        return back()->with('success', __('messages.announcement_published'));
    }

    public function show(Announcement $announcement)
    {
        return Inertia::render('Tenant/Manager/HR/Announcements', [
            'announcements' => Announcement::latest()->paginate(20),
            'selected' => $announcement,
        ]);
    }

    public function edit(Announcement $announcement)
    {
        return redirect()->route('tenant.manager.announcements.index');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'is_important' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        $announcement->update($data);

        return back()->with('success', __('messages.announcement_updated'));
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('success', __('messages.announcement_deleted'));
    }

    public function markRead(Announcement $announcement)
    {
        return back()->with('success', __('messages.marked_as_read'));
    }
}
