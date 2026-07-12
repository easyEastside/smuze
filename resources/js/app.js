import { FitAddon } from '@xterm/addon-fit';
import { Terminal } from '@xterm/xterm';

window.SmuzeTerminal = { FitAddon, Terminal };

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

document.addEventListener('DOMContentLoaded', () => {
    initializeFloatingCommandLog();
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

function initializeFloatingCommandLog() {
    const panel = document.getElementById('floating-command-log');

    if (! panel) {
        return;
    }

    const output = panel.querySelector('[data-command-log-output]');
    const status = panel.querySelector('[data-command-log-status]');
    const toggles = panel.querySelectorAll('[data-command-log-toggle]');
    const clearButton = panel.querySelector('[data-command-log-clear]');
    const serverId = panel.dataset.serverId;
    const storageKey = `smuze:server:${serverId}:command-log`;
    const collapsedKey = `smuze:server:${serverId}:command-log-collapsed`;

    if (! output || ! status || ! serverId) {
        return;
    }

    const readLog = () => {
        try {
            return localStorage.getItem(storageKey) || '';
        } catch {
            return '';
        }
    };

    const writeLog = (value) => {
        try {
            localStorage.setItem(storageKey, value.slice(-50000));
        } catch {
            // Ignore unavailable storage.
        }
    };

    const setCollapsed = (collapsed) => {
        output.classList.toggle('hidden', collapsed);
        toggles.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

            if (toggle.textContent.trim() === 'Minimieren' || toggle.textContent.trim() === 'Öffnen') {
                toggle.textContent = collapsed ? 'Öffnen' : 'Minimieren';
            }
        });

        try {
            localStorage.setItem(collapsedKey, collapsed ? '1' : '0');
        } catch {
            // Ignore unavailable storage.
        }
    };

    const append = (message, level = 'info') => {
        if (String(message || '').trim() === '') {
            return;
        }

        const timestamp = new Date().toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const prefix = level === 'error' ? 'ERR' : level === 'success' ? 'OK ' : 'CMD';
        const nextLog = `${readLog()}[${timestamp}] ${prefix} ${message}\n`;

        writeLog(nextLog);
        output.textContent = readLog();
        output.scrollTop = output.scrollHeight;
        panel.classList.remove('hidden');
    };

    output.textContent = readLog();
    panel.classList.remove('hidden');
    setCollapsed(readStoredBoolean(collapsedKey));
    output.scrollTop = output.scrollHeight;

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            setCollapsed(! output.classList.contains('hidden'));
        });
    });

    clearButton?.addEventListener('click', () => {
        writeLog('');
        output.textContent = '';
        status.textContent = 'Logs geleert';
    });

    window.SmuzeCommandLog = {
        open() {
            panel.classList.remove('hidden');
            setCollapsed(false);
        },
        write(message, level = 'info') {
            append(message, level);
        },
        status(message) {
            status.textContent = message;
        },
        clear() {
            writeLog('');
            output.textContent = '';
            status.textContent = 'Logs geleert';
        },
    };
}

function readStoredBoolean(key) {
    try {
        return localStorage.getItem(key) === '1';
    } catch {
        return false;
    }
}
