<x-layouts.admin title="Edit Achievement">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Edit achievement</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Update the achievement details.
            </p>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <form method="POST" action="{{ route('admin.achievements.update', $achievement) }}" class="max-w-2xl">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="key" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Key</label>
                    <input
                        type="text"
                        name="key"
                        id="key"
                        value="{{ old('key', $achievement->key) }}"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] font-mono dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('key') border-[#f53003] @enderror"
                    />
                    <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Changing this may break automated unlocks.</p>
                    @error('key')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Name</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name', $achievement->name) }}"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('name') border-[#f53003] @enderror"
                    />
                    @error('name')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Description</label>
                    <textarea
                        name="description"
                        id="description"
                        rows="3"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('description') border-[#f53003] @enderror"
                    >{{ old('description', $achievement->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="icon" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Icon (emoji)</label>
                    <input
                        type="text"
                        name="icon"
                        id="icon"
                        value="{{ old('icon', $achievement->icon) }}"
                        placeholder="🏆"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('icon') border-[#f53003] @enderror"
                    />
                    @error('icon')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reward_credits" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Reward credits</label>
                    <input
                        type="number"
                        name="reward_credits"
                        id="reward_credits"
                        value="{{ old('reward_credits', $achievement->reward_credits) }}"
                        min="0"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('reward_credits') border-[#f53003] @enderror"
                    />
                    @error('reward_credits')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="is_hidden"
                            value="1"
                            @checked(old('is_hidden', $achievement->is_hidden))
                            class="rounded border-[#19140020] text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615]"
                        />
                        <span class="text-[#1b1b18] dark:text-[#EDEDEC]">Hidden (only visible after unlocking)</span>
                    </label>
                </div>
            </div>

            <div class="mt-8 flex items-center gap-3">
                <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                    Update achievement
                </button>
                <a href="{{ route('admin.achievements.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
