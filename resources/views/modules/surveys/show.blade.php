<x-layouts.app :title="$survey->title">
    <section class="w-full max-w-3xl">
        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 shadow-[inset_0_0_0_1px_rgba(220,38,38,0.16)] dark:bg-red-950 dark:text-red-200 dark:shadow-[inset_0_0_0_1px_rgba(252,165,165,0.18)]">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Survey</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $survey->title }}</h1>
            @if ($survey->description)
                <p class="mt-3 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">{{ $survey->description }}</p>
            @endif
        </div>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            @if ($hasResponded)
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Results</h2>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">You have already taken this survey. Here are the current results.</p>
                    </div>
                    <a href="{{ route('surveys.index') }}" class="text-sm font-medium text-[#f53003] hover:underline dark:text-[#FF4433]">Back to surveys</a>
                </div>

                <div class="mt-8 space-y-8">
                    @foreach ($survey->questions as $question)
                        @php
                            $totalAnswers = $question->options->sum(fn ($option) => $option->answers->count());
                        @endphp

                        <div>
                            <h3 class="text-base font-semibold">{{ $question->question }}</h3>
                            <div class="mt-4 space-y-4">
                                @foreach ($question->options as $option)
                                    @php
                                        $count = $option->answers->count();
                                        $percentage = $totalAnswers > 0 ? round(($count / $totalAnswers) * 100) : 0;
                                        $isUserAnswer = in_array($option->id, $userAnswerOptionIds, true);
                                    @endphp

                                    <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A] {{ $isUserAnswer ? 'bg-[#f53003]/5 dark:bg-[#FF4433]/10' : '' }}">
                                        <div class="flex items-center justify-between gap-4 text-sm">
                                            <div class="flex items-center gap-2 font-medium">
                                                <span>{{ $option->label }}</span>
                                                @if ($isUserAnswer)
                                                    <span class="rounded-full bg-[#f53003]/10 px-2 py-0.5 text-xs text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">Your answer</span>
                                                @endif
                                            </div>
                                            <span class="text-[#706f6c] dark:text-[#A1A09A]">{{ $count }} {{ Str::plural('vote', $count) }} | {{ $percentage }}%</span>
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
            @else
                <form method="POST" action="{{ route('surveys.responses.store', $survey) }}" class="space-y-8">
                    @csrf

                    @foreach ($survey->questions as $question)
                        <fieldset>
                            <legend class="text-base font-semibold">{{ $question->question }}</legend>
                            <div class="mt-4 space-y-3">
                                @foreach ($question->options as $option)
                                    <label class="flex items-center gap-3 rounded-xl border border-[#19140020] p-3 text-sm hover:border-[#f53003] dark:border-[#3E3E3A] dark:hover:border-[#FF4433]">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" @checked((string) old("answers.{$question->id}") === (string) $option->id) class="text-[#f53003] dark:bg-[#161615]" />
                                        <span>{{ $option->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error("answers.{$question->id}")
                                <p class="mt-2 text-sm text-[#f53003]">{{ $message }}</p>
                            @enderror
                        </fieldset>
                    @endforeach

                    <div class="flex items-center gap-3">
                        <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                            Submit answers
                        </button>
                        <a href="{{ route('surveys.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Cancel</a>
                    </div>
                </form>
            @endif
        </div>
    </section>
</x-layouts.app>
