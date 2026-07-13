<x-layouts.app title="Shop">
    <section class="w-full max-w-5xl">
        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 shadow-[inset_0_0_0_1px_rgba(220,38,38,0.16)] dark:bg-red-950 dark:text-red-200 dark:shadow-[inset_0_0_0_1px_rgba(252,165,165,0.18)]">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div>
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Shop</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Browse items</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                    Purchase items using your credits.
                </p>
            </div>
        </div>

        @if ($items->isEmpty())
            <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">No items available in the shop right now.</p>
            </div>
        @else
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $item)
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        @if ($item->image_path)
                            <img src="{{ Storage::url($item->image_path) }}" alt="{{ $item->name }}" class="mb-4 h-40 w-full rounded-xl object-cover" />
                        @else
                            <div class="mb-4 flex h-40 w-full items-center justify-center rounded-xl bg-[#19140005] dark:bg-[#fffaed05]">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-12 text-[#706f6c] dark:text-[#A1A09A]">
                                    <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 0 0-4.242 0l-7 7a3 3 0 0 0 4.241 4.243h.001l.497-.5a.75.75 0 0 1 1.064 1.057l-.498.501-.002.002a4.5 4.5 0 0 1-6.364-6.364l7-7a4.5 4.5 0 0 1 6.368 6.36l-3.455 3.553A2.625 2.625 0 1 1 9.52 9.52l3.45-3.451a.75.75 0 1 1 1.061 1.06l-3.45 3.451a1.125 1.125 0 0 0 1.587 1.595l3.454-3.553a3 3 0 0 0 0-4.242Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        @endif

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold">{{ $item->name }}</h3>
                                @if ($item->short_description)
                                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $item->short_description }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 rounded-full bg-[#f53003]/10 px-3 py-1 text-sm font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">
                                {{ $item->price }} Credits
                            </span>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            @if ($item->stock !== null)
                                <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $item->stock }} in stock</span>
                            @else
                                <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Unlimited</span>
                            @endif

                            <div class="flex gap-2">
                                <a
                                    href="{{ route('shop.show', $item) }}"
                                    class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                                >
                                    View
                                </a>
                                <form method="POST" action="{{ route('shop.buy', $item) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input
                                        type="number"
                                        name="quantity"
                                        value="1"
                                        min="1"
                                        max="{{ $item->stock ?? 999 }}"
                                        class="w-14 rounded-lg border border-[#19140020] bg-white px-2 py-1.5 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                    />
                                    <button
                                        type="submit"
                                        class="rounded-lg bg-[#f53003] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]"
                                        @cannot('buy', $item) disabled="disabled" @endcannot
                                    >
                                        Buy
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.app>
