<x-layouts.admin title="Surveys">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Surveys</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">Manage multiple choice surveys and view responses.</p>
            </div>
            <a href="{{ route('admin.surveys.create') }}" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">Create survey</a>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-[#19140020] text-xs uppercase text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                <tr>
                    <th class="px-6 py-4">Title</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Availability</th>
                    <th class="px-6 py-4">Questions</th>
                    <th class="px-6 py-4">Responses</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#19140010] dark:divide-[#3E3E3A]">
                @forelse ($surveys as $survey)
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $survey->title }}</td>
                        <td class="px-6 py-4">{{ $survey->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-6 py-4 text-[#706f6c] dark:text-[#A1A09A]">
                            @if ($survey->starts_at && $survey->ends_at)
                                {{ $survey->starts_at->format('M j, Y H:i') }} - {{ $survey->ends_at->format('M j, Y H:i') }}
                            @elseif ($survey->starts_at)
                                From {{ $survey->starts_at->format('M j, Y H:i') }}
                            @elseif ($survey->ends_at)
                                Until {{ $survey->ends_at->format('M j, Y H:i') }}
                            @else
                                Always open
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $survey->questions_count }}</td>
                        <td class="px-6 py-4">{{ $survey->responses_count }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.surveys.show', $survey) }}" class="text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Results</a>
                                <a href="{{ route('admin.surveys.edit', $survey) }}" class="text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Edit</a>
                                <form method="POST" action="{{ route('admin.surveys.destroy', $survey) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-[#706f6c] dark:text-[#A1A09A]">No surveys created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $surveys->links() }}</div>
</x-layouts.admin>
