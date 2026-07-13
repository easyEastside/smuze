<x-layouts.app title="{{ $thread->subject }}">
    @php
        $currentUser = auth()->user();
        $otherParticipant = $thread->otherParticipant($currentUser);
    @endphp

    <section class="w-full max-w-4xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <a href="{{ route('messages.index') }}" class="text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-white">Back to messages</a>
                    <p class="mt-4 text-sm text-[#f53003] dark:text-[#FF4433]">Thread with {{ $otherParticipant->name }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $thread->subject }}</h1>
                </div>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $thread->messages->count() }} message(s)</p>
            </div>

            <div class="mt-8 flex flex-col gap-4">
                @foreach ($thread->messages as $message)
                    @php($isOwnMessage = $message->sender_id === $currentUser->id)

                    <div class="flex {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
                        <article class="max-w-[42rem] rounded-2xl px-4 py-3 {{ $isOwnMessage ? 'bg-[#1b1b18] text-white dark:bg-[#eeeeec] dark:text-[#1C1C1A]' : 'bg-[#f5f5f2] text-[#1b1b18] dark:bg-[#1f1f1d] dark:text-[#EDEDEC]' }}">
                            <div class="flex flex-wrap items-center gap-2 text-xs {{ $isOwnMessage ? 'text-white/70 dark:text-[#1C1C1A]/70' : 'text-[#706f6c] dark:text-[#A1A09A]' }}">
                                <span>{{ $message->sender->name }}</span>
                                <span>{{ $message->created_at->format('M j, Y H:i') }}</span>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6">{{ $message->body }}</p>
                        </article>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('messages.reply', $thread) }}" class="mt-8 flex flex-col gap-3">
                @csrf

                <label for="body" class="text-sm font-medium">Reply</label>
                <textarea id="body" name="body" rows="4" class="w-full rounded-sm border border-[#19140035] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#0a0a0a]" maxlength="2000">{{ old('body') }}</textarea>
                @error('body')
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                @enderror

                <button type="submit" class="w-fit rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
                    Send message
                </button>
            </form>
        </div>
    </section>
</x-layouts.app>
