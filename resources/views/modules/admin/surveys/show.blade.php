<x-layouts.admin :title="$survey->title">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Survey results</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $survey->title }}</h1>
        <p class="mt-3 text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $survey->responses->count() }} responses</p>
    </div>

    <div class="mt-6 space-y-6">
        @foreach ($survey->questions as $question)
            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <h2 class="text-xl font-semibold">{{ $question->question }}</h2>
                <div class="mt-4 space-y-3">
                    @php
                        $totalAnswers = $question->options->sum(fn ($option) => $option->answers->count());
                    @endphp

                    @foreach ($question->options as $option)
                        @php
                            $count = $option->answers->count();
                            $percentage = $totalAnswers > 0 ? round(($count / $totalAnswers) * 100) : 0;
                        @endphp

                        <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span>{{ $option->label }}</span>
                                <span class="font-medium text-[#f53003] dark:text-[#FF4433]">{{ $count }} {{ Str::plural('vote', $count) }} | {{ $percentage }}%</span>
                            </div>
                            <div class="mt-3 h-3 overflow-hidden rounded-full bg-[#19140010] dark:bg-[#fffaed12]" aria-label="{{ $percentage }} percent">
                                <div class="h-full rounded-full bg-[#f53003] dark:bg-[#FF4433]" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <h2 class="text-xl font-semibold">Responses</h2>
        <div class="mt-4 space-y-4">
            @forelse ($survey->responses as $response)
                <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                    <p class="text-sm font-medium">{{ $response->user->name }}</p>
                    <div class="mt-2 space-y-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        @foreach ($response->answers as $answer)
                            <p>{{ $answer->question->question }}: {{ $answer->option->label }}</p>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">No responses yet.</p>
            @endforelse
        </div>
    </div>
</x-layouts.admin>
