<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Requests\Messaging\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        // Authorisation (can post = can view) lives in the request.
        $message = new Message($request->validated());
        $message->conversation_id = $conversation->id;
        $message->sender_id = $request->user()->id;
        $message->save();

        $conversation->markReadBy($request->user());
        $conversation->touch();

        return redirect()->route('messages.show', $conversation);
    }
}
