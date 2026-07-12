<x-layouts.app title="Messages">
    <section class="w-full max-w-5xl">
        @if (session('status'))
            <div class="mb-6 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-800 shadow-[inset_0_0_0_1px_rgba(22,101,52,0.16)] dark:bg-green-950 dark:text-green-200 dark:shadow-[inset_0_0_0_1px_rgba(187,247,208,0.18)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
            <aside class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">New thread</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight">Start a conversation</h1>
                <p class="mt-2 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">Create a separate thread for every topic, even with the same user.</p>

                <form method="POST" action="{{ route('messages.store') }}" class="mt-6 flex flex-col gap-4">
                    @csrf

                    <div>
                        <label for="recipient_id" class="text-sm font-medium">Recipient</label>
                        <select id="recipient_id" name="recipient_id" class="mt-2 w-full rounded-sm border border-[#19140035] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                            <option value="">Choose a user</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((int) old('recipient_id') === $user->id)>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('recipient_id')
                            <p class="mt-2 text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="subject" class="text-sm font-medium">Subject</label>
                        <input id="subject" name="subject" value="{{ old('subject') }}" class="mt-2 w-full rounded-sm border border-[#19140035] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#0a0a0a]" maxlength="120">
                        @error('subject')
                            <p class="mt-2 text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="body" class="text-sm font-medium">Message</label>
                        <textarea id="body" name="body" rows="5" class="mt-2 w-full rounded-sm border border-[#19140035] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#0a0a0a]" maxlength="2000">{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-2 text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
                        Start thread
                    </button>
                </form>
            </aside>

            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Inbox</p>
                        <h2 class="mt-2 text-3xl font-semibold tracking-tight">Message threads</h2>
                    </div>
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $threads->count() }} thread(s)</p>
                </div>

                <div class="mt-6 overflow-hidden rounded-xl border border-[#19140020] dark:border-[#3E3E3A]">
                    @forelse ($threads as $thread)
                        @php
                            $otherParticipant = $thread->otherParticipant(auth()->user());
                            $latestMessage = $thread->latestMessage;
                        @endphp

                        <a href="{{ route('messages.show', $thread) }}" class="block border-b border-[#19140012] p-4 transition hover:bg-[#f5f5f2] last:border-b-0 dark:border-[#3E3E3A] dark:hover:bg-[#1f1f1d]">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate text-base font-semibold">{{ $thread->subject }}</h3>
                                        @if ($thread->unread_messages_count > 0)
                                            <span class="rounded-full bg-[#f53003]/10 px-2.5 py-1 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">
                                                {{ $thread->unread_messages_count }} unread
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">With {{ $otherParticipant->name }}</p>
                                    <p class="mt-3 line-clamp-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        @if ($latestMessage)
                                            <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{{ $latestMessage->sender->name }}:</span>
                                            {{ $latestMessage->body }}
                                        @else
                                            No messages yet.
                                        @endif
                                    </p>
                                </div>
                                <time class="shrink-0 text-sm text-[#706f6c] dark:text-[#A1A09A]" datetime="{{ $thread->updated_at->toIso8601String() }}">
                                    {{ $thread->updated_at->diffForHumans() }}
                                </time>
                            </div>
                        </a>
                    @empty
                        <div class="p-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No message threads yet. Start one with the form on this page.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
