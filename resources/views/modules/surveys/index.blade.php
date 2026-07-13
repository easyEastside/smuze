<x-layouts.app title="Surveys">
    <section class="w-full max-w-5xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Surveys</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Take a survey</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Share your feedback by answering active multiple choice surveys.
            </p>
        </div>

        @if ($surveys->isEmpty())
            <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">No active surveys are available right now.</p>
            </div>
        @else
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                @foreach ($surveys as $survey)
                    <article class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-semibold">{{ $survey->title }}</h2>
                                @if ($survey->description)
                                    <p class="mt-2 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">{{ $survey->description }}</p>
                                @endif
                                <p class="mt-3 text-xs font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]">Availability</p>
                                <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    @if ($survey->starts_at && $survey->ends_at)
                                        {{ $survey->starts_at->format('M j, Y H:i') }} - {{ $survey->ends_at->format('M j, Y H:i') }}
                                    @elseif ($survey->starts_at)
                                        From {{ $survey->starts_at->format('M j, Y H:i') }}
                                    @elseif ($survey->ends_at)
                                        Until {{ $survey->ends_at->format('M j, Y H:i') }}
                                    @else
                                        Always open
                                    @endif
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full bg-[#f53003]/10 px-3 py-1 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">
                                {{ $survey->responses_count }} responses
                            </span>
                        </div>

                        <a href="{{ route('surveys.show', $survey) }}" class="mt-6 inline-flex rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                            Open survey
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.app>
