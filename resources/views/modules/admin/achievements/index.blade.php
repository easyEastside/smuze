<x-layouts.admin title="Achievements">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Achievements</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Manage achievements that users can unlock.
            </p>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div class="flex items-center justify-between">
            <a
                href="{{ route('admin.achievements.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                </svg>
                New achievement
            </a>
        </div>

        <div class="mt-6 overflow-hidden rounded-xl border border-[#19140020] dark:border-[#3E3E3A]">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#19140012] bg-[#19140005] dark:border-[#3E3E3A] dark:bg-[#fffaed05]">
                        <th class="px-4 py-3 text-left font-medium text-[#706f6c] dark:text-[#A1A09A]">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-[#706f6c] dark:text-[#A1A09A]">Key</th>
                        <th class="px-4 py-3 text-left font-medium text-[#706f6c] dark:text-[#A1A09A]">Reward</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-[#706f6c] dark:text-[#A1A09A] sm:table-cell">Users</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-[#706f6c] dark:text-[#A1A09A] sm:table-cell">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-[#706f6c] dark:text-[#A1A09A]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($achievements as $achievement)
                        <tr class="border-b border-[#19140012] last:border-b-0 dark:border-[#3E3E3A]">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($achievement->icon)
                                        <span class="text-lg">{{ $achievement->icon }}</span>
                                    @else
                                        <span class="flex size-8 items-center justify-center rounded-lg bg-[#f53003]/10 text-sm dark:bg-[#FF4433]/15">
                                            🏆
                                        </span>
                                    @endif
                                    <span class="font-medium">{{ $achievement->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $achievement->key }}</td>
                            <td class="px-4 py-3">
                                @if ($achievement->reward_credits > 0)
                                    <span class="rounded-full bg-[#f53003]/10 px-2.5 py-1 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">
                                        {{ $achievement->reward_credits }}
                                    </span>
                                @else
                                    <span class="text-[#706f6c] dark:text-[#A1A09A]">—</span>
                                @endif
                            </td>
                            <td class="hidden px-4 py-3 text-[#706f6c] dark:text-[#A1A09A] sm:table-cell">{{ $achievement->users_count }}</td>
                            <td class="hidden px-4 py-3 sm:table-cell">
                                @if ($achievement->is_hidden)
                                    <span class="rounded-full bg-[#19140008] px-2.5 py-1 text-xs font-medium text-[#706f6c] dark:bg-[#ffffff0f] dark:text-[#A1A09A]">Hidden</span>
                                @else
                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">Visible</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.achievements.edit', $achievement) }}" class="text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Edit</a>
                                <form action="{{ route('admin.achievements.destroy', $achievement) }}" method="POST" class="ml-3 inline" onsubmit="return confirm('Delete achievement {{ $achievement->name }}? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                No achievements found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $achievements->links() }}
        </div>
    </div>
</x-layouts.admin>
