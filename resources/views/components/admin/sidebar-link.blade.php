@props(['active' => false, 'disabled' => false, 'href' => null])

@php
    $classes = $active
        ? 'flex items-center gap-3 rounded-lg bg-[#f53003]/10 px-3 py-2 text-sm font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]'
        : ($disabled
            ? 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-[#706f6c]/40 dark:text-[#A1A09A]/40 cursor-not-allowed'
            : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-[#706f6c] hover:bg-[#19140008] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#fffaed0a] dark:hover:text-white');
@endphp

@if ($disabled)
    <span {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}

        <span class="ml-auto text-[10px] font-medium uppercase tracking-wider text-[#706f6c]/30 dark:text-[#A1A09A]/30">Soon</span>
    </span>
@else
    <a {{ $attributes->merge(['class' => $classes, 'href' => $href]) }}>
        {{ $slot }}
    </a>
@endif
