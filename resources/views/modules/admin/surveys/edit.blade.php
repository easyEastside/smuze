<x-layouts.admin title="Edit Survey">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Edit survey</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">Updating questions replaces the existing answer structure.</p>
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <form method="POST" action="{{ route('admin.surveys.update', $survey) }}" class="max-w-2xl space-y-6">
            @csrf
            @method('PUT')
            @include('modules.admin.surveys.form', ['survey' => $survey])
        </form>
    </div>
</x-layouts.admin>
