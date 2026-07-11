@php
    $firstQuestion = $survey?->questions->first();
    $options = $firstQuestion?->options ?? collect();
    $oldOptions = old('questions.0.options');
    $optionValues = is_array($oldOptions)
        ? collect($oldOptions)->pluck('label')->values()
        : $options->pluck('label');

    if ($optionValues->count() < 2) {
        $optionValues = collect($optionValues)->pad(2, null);
    }
@endphp

<div>
    <label for="title" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Title</label>
    <input type="text" name="title" id="title" value="{{ old('title', $survey?->title) }}" class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('title') border-[#f53003] @enderror" />
    @error('title')<p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>@enderror
</div>

<div>
    <label for="description" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Description</label>
    <textarea name="description" id="description" rows="3" class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('description') border-[#f53003] @enderror">{{ old('description', $survey?->description) }}</textarea>
    @error('description')<p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <label for="starts_at" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Starts at</label>
        <input type="datetime-local" name="starts_at" id="starts_at" value="{{ old('starts_at', $survey?->starts_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('starts_at') border-[#f53003] @enderror" />
        @error('starts_at')<p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="ends_at" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Ends at</label>
        <input type="datetime-local" name="ends_at" id="ends_at" value="{{ old('ends_at', $survey?->ends_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('ends_at') border-[#f53003] @enderror" />
        @error('ends_at')<p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>@enderror
    </div>
</div>

<div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
    <label for="question" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Question</label>
    <input type="text" name="questions[0][question]" id="question" value="{{ old('questions.0.question', $firstQuestion?->question) }}" class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('questions.0.question') border-[#f53003] @enderror" />
    @error('questions.0.question')<p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>@enderror

    <div class="mt-4 flex items-center justify-between gap-4">
        <p class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Options</p>
        <button type="button" data-survey-add-option class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
            Add option
        </button>
    </div>

    <div data-survey-options class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($optionValues as $index => $optionValue)
            <div data-survey-option>
                <label for="option_{{ $index }}" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Option {{ $index + 1 }}</label>
                <div class="mt-1 flex gap-2">
                    <input type="text" name="questions[0][options][{{ $index }}][label]" id="option_{{ $index }}" value="{{ $optionValue }}" class="w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error("questions.0.options.{$index}.label") border-[#f53003] @enderror" />
                    <button type="button" data-survey-remove-option class="rounded-lg border border-[#19140035] px-3 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Remove</button>
                </div>
                @error("questions.0.options.{$index}.label")<p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
    @error('questions.0.options')<p class="mt-2 text-sm text-[#f53003]">{{ $message }}</p>@enderror
</div>

<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $survey?->is_active ?? true)) class="rounded border-[#19140020] text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615]" />
    <span class="text-[#1b1b18] dark:text-[#EDEDEC]">Active</span>
</label>

<div class="flex items-center gap-3">
    <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">Save survey</button>
    <a href="{{ route('admin.surveys.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Cancel</a>
</div>
