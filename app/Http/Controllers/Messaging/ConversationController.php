<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Requests\Messaging\StoreDmRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    /** The inbox: every conversation this user can see, latest activity first. */
    public function index(): View
    {
        $user = auth()->user();

        $conversations = Conversation::whereIn('id', $user->accessibleConversationIds())
            ->with(['latestMessage.sender', 'participants', 'placement.student', 'placement.organization'])
            ->get()
            // Placement threads only surface once someone has written in them.
            ->filter(fn ($c) => $c->latestMessage !== null || ! $c->isPlacementThread())
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at ?? $c->created_at)
            ->values();

        return view('messages.index', compact('conversations'));
    }

    public function show(Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $conversation->load(['participants', 'placement.student', 'placement.organization']);

        $messages = $conversation->messages()->with('sender')->orderBy('created_at')->get();

        $conversation->markReadBy(auth()->user());

        return view('messages.show', compact('conversation', 'messages'));
    }

    /** New-DM form: pick a recipient from the role-scoped contact list. */
    public function create(): View
    {
        return view('messages.create', [
            'contacts' => auth()->user()->contactableUsers(),
        ]);
    }

    public function storeDm(StoreDmRequest $request): RedirectResponse
    {
        $user = $request->user();
        $recipient = User::findOrFail((int) $request->validated()['recipient_id']);

        $conversation = Conversation::dmBetween($user, $recipient);

        $message = new Message($request->safe()->only('body'));
        $message->conversation_id = $conversation->id;
        $message->sender_id = $user->id;
        $message->save();

        $conversation->markReadBy($user);

        return redirect()->route('messages.show', $conversation);
    }

    /**
     * Lightweight polling endpoint: messages newer than ?after={id}. The open
     * thread page calls this every few seconds so replies appear without a
     * refresh — plain HTTP, no websockets.
     */
    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $user = $request->user();

        $messages = $conversation->messages()
            ->with('sender')
            ->where('id', '>', (int) $request->query('after', 0))
            ->orderBy('id')
            ->get();

        if ($messages->isNotEmpty()) {
            // The viewer has this thread open, so what we return is read.
            $conversation->markReadBy($user);
        }

        return response()->json([
            'messages' => $messages->map(fn (Message $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'mine' => $m->sender_id === $user->id,
                'sender' => $m->sender?->name ?? 'مستخدم محذوف',
                'role' => $m->sender?->role->label() ?? '',
                'time' => $m->created_at->format('Y/m/d H:i'),
            ])->values(),
        ]);
    }

    /** Open (or create) the discussion thread of a placement. */
    public function openPlacementThread(Placement $placement): RedirectResponse
    {
        $this->authorize('discuss', $placement);

        $conversation = Conversation::forPlacement($placement);

        return redirect()->route('messages.show', $conversation);
    }
}
