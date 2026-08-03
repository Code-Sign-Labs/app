(() => {
    const STORAGE_KEY = 'codesign-theme';

    const SUN = '<svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>';
    const MOON = '<svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>';

    function createToggle() {
        const btn = document.createElement('button');
        btn.id = 'theme-toggle';
        btn.className = 'flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition';
        btn.style.cursor = 'pointer';
        btn.title = 'Toggle dark mode';
        btn.innerHTML = document.documentElement.classList.contains('dark') ? SUN : MOON;
        btn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            btn.innerHTML = isDark ? SUN : MOON;
            localStorage.setItem(STORAGE_KEY, isDark ? 'dark' : 'light');
        });
        return btn;
    }

    function injectToggle() {
        // Find the topbar's right-side container (the div with flex items-center gap-4)
        const headers = document.querySelectorAll('header');
        headers.forEach(header => {
            if (document.getElementById('theme-toggle-injected')) return;
            const rightContainer = header.querySelector('.flex.items-center.gap-4, .flex.items-center.gap-2, .flex.items-center.gap-3');
            if (rightContainer) {
                const toggle = createToggle();
                toggle.id = 'theme-toggle-injected';
                rightContainer.insertBefore(toggle, rightContainer.firstChild);
            }
        });
    }

    const saved = localStorage.getItem(STORAGE_KEY);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (saved === 'dark' || (!saved && prefersDark)) {
        document.documentElement.classList.add('dark');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectToggle);
    } else {
        injectToggle();
    }
})();