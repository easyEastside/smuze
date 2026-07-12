<x-layouts.app title="{{ $profileUser->name }}">
    <section class="w-full max-w-5xl">
        <div class="overflow-hidden rounded-2xl bg-white shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
            <div class="bg-gradient-to-br from-[#f53003]/15 via-transparent to-[#1b1b18]/5 p-6 dark:from-[#FF4433]/20 dark:to-white/5 sm:p-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex items-center gap-5">
                        <div class="flex size-24 items-center justify-center overflow-hidden rounded-3xl bg-white text-3xl font-semibold text-[#f53003] shadow-[inset_0_0_0_1px_rgba(26,26,0,0.12)] dark:bg-[#0a0a0a] dark:text-[#FF4433] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ $profileUser->name }} avatar" class="size-full object-cover">
                            @else
                                {{ str($profileUser->name)->substr(0, 1)->upper() }}
                            @endif
                        </div>

                        <div>
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Guest profile</p>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $profileUser->name }}</h1>
                            <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Member since {{ $profileUser->created_at?->format('M j, Y') ?? 'unknown' }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:w-72">
                        <div class="rounded-2xl bg-white/80 p-4 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.10)] dark:bg-[#0a0a0a]/80 dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Credits</p>
                            <p class="mt-1 text-2xl font-bold {{ $profileUser->credits >= 0 ? 'text-green-600 dark:text-green-400' : 'text-[#f53003] dark:text-[#FF4433]' }}">
                                {{ number_format($profileUser->credits) }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-white/80 p-4 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.10)] dark:bg-[#0a0a0a]/80 dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Items</p>
                            <p class="mt-1 text-2xl font-bold">{{ $inventoryItems->sum('total_quantity') }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Roles</span>

                    @forelse ($profileUser->roles as $role)
                        <span class="rounded-full bg-[#1b1b18] px-3 py-1 text-xs font-medium text-white dark:bg-[#eeeeec] dark:text-[#1C1C1A]">
                            {{ str($role->name)->headline() }}
                        </span>
                    @empty
                        <span class="rounded-full bg-[#19140008] px-3 py-1 text-xs font-medium text-[#706f6c] dark:bg-[#fffaed08] dark:text-[#A1A09A]">
                            No roles
                        </span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div>
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Public inventory</p>
                <h2 class="mt-2 text-2xl font-semibold">Visible items</h2>
                <p class="mt-2 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                    Items this user owns. Private account details stay hidden.
                </p>
            </div>

            @if ($inventoryItems->isEmpty())
                <div class="mt-6 rounded-xl border border-dashed border-[#19140035] p-6 text-sm text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                    {{ $profileUser->name }} does not have any visible items yet.
                </div>
            @else
                <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($inventoryItems as $inventoryItem)
                        @php $item = $inventoryItem->shopItem; @endphp

                        <article class="rounded-2xl border border-[#19140014] p-5 dark:border-[#3E3E3A]">
                            @if ($item->image_path)
                                <img src="{{ Storage::url($item->image_path) }}" alt="{{ $item->name }}" class="mb-4 h-32 w-full rounded-xl object-cover">
                            @else
                                <div class="mb-4 flex h-32 w-full items-center justify-center rounded-xl bg-[#19140005] dark:bg-[#fffaed05]">
                                    <span class="text-2xl font-semibold text-[#706f6c] dark:text-[#A1A09A]">{{ str($item->name)->substr(0, 1)->upper() }}</span>
                                </div>
                            @endif

                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold">{{ $item->name }}</h3>
                                    @if ($item->short_description)
                                        <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $item->short_description }}</p>
                                    @endif
                                </div>

                                <span class="shrink-0 rounded-full bg-[#f53003]/10 px-3 py-1 text-sm font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">
                                    x{{ $inventoryItem->total_quantity }}
                                </span>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.app>
