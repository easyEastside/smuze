document.addEventListener('click', (event) => {
    const navbarToggle = event.target.closest('[data-navbar-toggle]');

    if (navbarToggle) {
        toggleNavbarMenu(navbarToggle);

        return;
    }

    closeNavbarMenus(event.target.closest('[data-navbar-menu]'));

    const addButton = event.target.closest('[data-survey-add-option]');

    if (addButton) {
        const container = document.querySelector('[data-survey-options]');

        if (! container) {
            return;
        }

        reindexSurveyOptions(container);

        const index = container.querySelectorAll('[data-survey-option]').length;
        const wrapper = document.createElement('div');

        wrapper.dataset.surveyOption = '';
        wrapper.innerHTML = `
            <label for="option_${index}" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Option ${index + 1}</label>
            <div class="mt-1 flex gap-2">
                <input type="text" name="questions[0][options][${index}][label]" id="option_${index}" class="w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]" />
                <button type="button" data-survey-remove-option class="rounded-lg border border-[#19140035] px-3 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Remove</button>
            </div>
        `;

        container.append(wrapper);
        wrapper.querySelector('input')?.focus();

        return;
    }

    const removeButton = event.target.closest('[data-survey-remove-option]');

    if (! removeButton) {
        return;
    }

    const container = document.querySelector('[data-survey-options]');

    if (! container || container.querySelectorAll('[data-survey-option]').length <= 2) {
        return;
    }

    removeButton.closest('[data-survey-option]')?.remove();
    reindexSurveyOptions(container);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeNavbarMenus();
    }
});

function toggleNavbarMenu(toggle) {
    const menuName = toggle.dataset.navbarToggle;
    const menu = document.querySelector(`[data-navbar-menu="${menuName}"]`);

    if (! menu) {
        return;
    }

    const isOpening = menu.classList.contains('hidden');

    closeNavbarMenus(menu);

    menu.classList.toggle('hidden', ! isOpening);
    toggle.setAttribute('aria-expanded', isOpening ? 'true' : 'false');
}

function closeNavbarMenus(exceptMenu = null) {
    document.querySelectorAll('[data-navbar-menu]').forEach((menu) => {
        if (menu === exceptMenu) {
            return;
        }

        menu.classList.add('hidden');

        const toggle = document.querySelector(`[data-navbar-toggle="${menu.dataset.navbarMenu}"]`);

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}

window.reportError = function (message, source, details = {}) {
    const box = document.createElement('span');
    box.className = 'ml-2 inline-flex items-center gap-1';

    const copyBtn = document.createElement('button');
    copyBtn.className = 'rounded-lg border border-[#19140035] px-2 py-0.5 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]';
    copyBtn.textContent = 'Kopieren';
    copyBtn.addEventListener('click', () => {
        navigator.clipboard.writeText(message).then(() => {
            copyBtn.textContent = '✓';
            setTimeout(() => { copyBtn.textContent = 'Kopieren'; }, 1500);
        });
    });
    box.appendChild(copyBtn);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const reportBtn = document.createElement('button');
    reportBtn.className = 'rounded-lg border border-[#19140035] px-2 py-0.5 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]';
    reportBtn.textContent = 'Fehler melden';
    reportBtn.addEventListener('click', async () => {
        reportBtn.disabled = true;
        reportBtn.textContent = '...';
        try {
            const res = await fetch('/errors/report', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
                body: JSON.stringify({ message, source, details }),
            });
            const data = await res.json();
            reportBtn.textContent = data.success ? '✓' : '✗';
        } catch {
            reportBtn.textContent = '✗';
        }
    });
    box.appendChild(reportBtn);

    return box;
};

function getOrCreateToastContainer() {
    let container = document.getElementById('dynamic-toasts-container');
    if (container) return container;

    container = document.createElement('div');
    container.id = 'dynamic-toasts-container';
    container.className = 'pointer-events-none fixed bottom-4 right-4 z-50 flex w-full max-w-sm flex-col gap-2';
    container.setAttribute('role', 'status');
    container.setAttribute('aria-live', 'polite');
    document.body.appendChild(container);
    return container;
}

window.showToast = function (message, type = 'success') {
    const container = getOrCreateToastContainer();
    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg transition-all duration-300 ease-out';

    const classes = {
        success: 'border-green-200 bg-green-50 text-green-800 dark:border-green-800/30 dark:bg-green-900/20 dark:text-green-300',
        error: 'border-red-200 bg-red-50 text-red-800 dark:border-red-800/30 dark:bg-red-900/20 dark:text-red-300',
        info: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800/30 dark:bg-blue-900/20 dark:text-blue-300',
    };
    toast.className += ' ' + (classes[type] || classes.success);

    toast.innerHTML = '<span class="flex-1">' + message + '</span>'
        + '<button type="button" class="shrink-0 rounded-lg p-0.5 opacity-60 hover:opacity-100" aria-label="Schließen">'
        + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">'
        + '<path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg></button>';

    toast.style.transform = 'translateY(16px)';
    toast.style.opacity = '0';
    requestAnimationFrame(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    });

    const closeBtn = toast.querySelector('button');
    closeBtn.addEventListener('click', () => dismissToast(toast));

    container.appendChild(toast);

    setTimeout(() => dismissToast(toast), 5000);
};

function dismissToast(toast) {
    if (toast.dataset.dismissing) return;
    toast.dataset.dismissing = 'true';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(16px)';
    setTimeout(() => toast.remove(), 300);
}

function reindexSurveyOptions(container) {
    container.querySelectorAll('[data-survey-option]').forEach((option, index) => {
        const label = option.querySelector('label');
        const input = option.querySelector('input');

        if (label) {
            label.setAttribute('for', `option_${index}`);
            label.textContent = `Option ${index + 1}`;
        }

        if (input) {
            input.id = `option_${index}`;
            input.name = `questions[0][options][${index}][label]`;
        }
    });
}
