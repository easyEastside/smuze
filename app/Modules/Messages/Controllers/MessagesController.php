<?php

namespace App\Modules\Messages\Controllers;

use App\Models\MessageThread;
use App\Models\User;
use App\Modules\Achievements\Actions\UnlockAchievement;
use App\Modules\Messages\Requests\StartThreadRequest;
use App\Modules\Messages\Requests\StoreMessageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessagesController
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $threads = MessageThread::query()
            ->forUser($user)
            ->with([
                'participantOne:id,name,email',
                'participantTwo:id,name,email',
                'latestMessage.sender:id,name',
            ])
            ->withCount([
                'messages as unread_messages_count' => fn ($query) => $query
                    ->where('sender_id', '!=', $user->id)
                    ->whereNull('read_at'),
            ])
            ->orderByDesc('updated_at')
            ->get();

        $users = User::query()
            ->whereKeyNot($user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('modules.messages.index', compact('threads', 'users'));
    }

    public function store(StartThreadRequest $request): RedirectResponse
    {
        $user = $request->user();
        $recipient = User::findOrFail($request->integer('recipient_id'));
        $participantIds = collect([$user->id, $recipient->id])->sort()->values();

        $thread = MessageThread::create([
            'participant_one_id' => $participantIds[0],
            'participant_two_id' => $participantIds[1],
            'subject' => $request->string('subject')->toString(),
        ]);

        $thread->messages()->create([
            'sender_id' => $user->id,
            'body' => $request->string('body')->toString(),
        ]);

        app(UnlockAchievement::class)->handle($user, 'messenger');

        return redirect()->route('messages.show', $thread)->with('status', 'Thread started successfully.');
    }

    public function show(Request $request, MessageThread $messageThread): View
    {
        $this->authorizeThreadAccess($messageThread, $request->user());

        $messageThread->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messageThread->load([
            'participantOne:id,name,email',
            'participantTwo:id,name,email',
            'messages.sender:id,name',
        ]);

        return view('modules.messages.show', ['thread' => $messageThread]);
    }

    public function reply(StoreMessageRequest $request, MessageThread $messageThread): RedirectResponse
    {
        $this->authorizeThreadAccess($messageThread, $request->user());

        $messageThread->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->string('body')->toString(),
        ]);

        $messageThread->touch();

        return redirect()->route('messages.show', $messageThread)->with('status', 'Message sent.');
    }

    private function authorizeThreadAccess(MessageThread $messageThread, User $user): void
    {
        if (! $messageThread->includes($user)) {
            abort(403);
        }
    }
}
