<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Conversation;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index()
    {
        $user = Auth::guard('tenant')->user();

        $conversations = Conversation::with(['members', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->get()
            ->map(function ($conv) use ($user) {
                $other = $conv->members->firstWhere('id', '!=', $user->id);

                return [
                    'id' => $conv->id,
                    'with' => $other ? ['name' => $other->name, 'id' => $other->id] : null,
                    'last_message' => $conv->messages->first(),
                    'unread' => $conv->messages()
                        ->where('user_id', '!=', $user->id)
                        ->whereNull('read_at')
                        ->count(),
                    'messages' => [],
                ];
            });

        $users = User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->get(['id', 'name']);

        return Inertia::render('Tenant/Manager/Messages/Index', [
            'conversations' => $conversations,
            'users' => $users,
        ]);
    }

    public function show(Conversation $conversation)
    {
        $user = Auth::guard('tenant')->user();

        $conversation->load(['messages' => fn ($q) => $q->with('user')->orderBy('created_at')]);

        // Mark messages as read
        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages->map(fn ($m) => [
            'id' => $m->id,
            'body' => $m->body,
            'is_mine' => $m->user_id === $user->id,
            'created_at' => $m->created_at,
        ]);

        return response()->json(['messages' => $messages]);
    }

    public function createConversation(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'body' => 'required|string|max:5000',
        ]);

        $user = Auth::guard('tenant')->user();

        // Check if conversation already exists
        $existing = Conversation::whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('members', fn ($q) => $q->where('user_id', $request->recipient_id))
            ->where('is_group', false)
            ->first();

        if ($existing) {
            $existing->messages()->create([
                'user_id' => $user->id,
                'body' => $request->body,
            ]);

            return redirect()->route('tenant.manager.messages.index');
        }

        $conv = Conversation::create(['is_group' => false]);
        $conv->members()->attach([$user->id, $request->recipient_id]);
        $conv->messages()->create([
            'user_id' => $user->id,
            'body' => $request->body,
        ]);

        return redirect()->route('tenant.manager.messages.index')
            ->with('success', __('messages.conversation_started'));
    }

    public function store(Request $request, Conversation $conversation)
    {
        $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $user = Auth::guard('tenant')->user();

        $conversation->messages()->create([
            'user_id' => $user->id,
            'body' => $request->body,
        ]);

        return back();
    }

    public function markRead(Conversation $conversation)
    {
        $user = Auth::guard('tenant')->user();

        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
