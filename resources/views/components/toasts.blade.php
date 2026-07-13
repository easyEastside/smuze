@php
    $toasts = [];

    if (session('flash.success')) {
        $toasts[] = ['type' => 'success', 'message' => session('flash.success')];
    }

    if (session('flash.error')) {
        $toasts[] = ['type' => 'error', 'message' => session('flash.error')];
    }

    if (session('status')) {
        $toasts[] = ['type' => 'info', 'message' => session('status')];
    }

    if (session('error')) {
        $toasts[] = ['type' => 'error', 'message' => session('error')];
    }
@endphp

@if ($toasts)
    <div
        id="toasts-container"
        class="pointer-events-none fixed bottom-4 right-4 z-50 flex w-full max-w-sm flex-col gap-2"
        role="status"
        aria-live="polite"
    >
        @foreach ($toasts as $toast)
            @php
                $classes = match ($toast['type']) {
                    'success' => 'border-green-200 bg-green-50 text-green-800 dark:border-green-800/30 dark:bg-green-900/20 dark:text-green-300',
                    'error' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-800/30 dark:bg-red-900/20 dark:text-red-300',
                    'info' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800/30 dark:bg-blue-900/20 dark:text-blue-300',
                };
            @endphp
            <div
                data-toast
                class="pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg transition-all duration-300 ease-out {{ $classes }}"
            >
                <span class="flex-1">{{ $toast['message'] }}</span>
                <button
                    type="button"
                    data-toast-close
                    class="shrink-0 rounded-lg p-0.5 opacity-60 hover:opacity-100"
                    aria-label="Schließen"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            </div>
        @endforeach
    </div>

    @push('scripts')
        <script>
            (function () {
                const container = document.getElementById('toasts-container');
                if (! container) return;

                container.querySelectorAll('[data-toast]').forEach((toast) => {
                    const closeBtn = toast.querySelector('[data-toast-close]');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', () => dismiss(toast));
                    }

                    setTimeout(() => dismiss(toast), 5000);
                });

                function dismiss(toast) {
                    if (toast.dataset.dismissing) return;
                    toast.dataset.dismissing = 'true';
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(16px)';
                    setTimeout(() => toast.remove(), 300);
                }
            })();
        </script>
    @endpush
@endif
