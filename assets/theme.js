(() => {
    const button = document.querySelector('[data-theme-toggle]');
    if (!button) return;

    const icon = button.querySelector('[data-theme-icon]');
    const label = button.querySelector('[data-theme-label]');

    const updateButton = () => {
        const isDark = document.documentElement.dataset.theme === 'dark';
        button.setAttribute('aria-label', `Switch to ${isDark ? 'light' : 'dark'} mode`);
        button.setAttribute('aria-pressed', String(isDark));
        label.textContent = isDark ? 'Light mode' : 'Dark mode';
        icon.innerHTML = isDark ? '&#9788;' : '&#9790;';
    };

    updateButton();
    button.addEventListener('click', () => {
        const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = nextTheme;
        try {
            localStorage.setItem('swd-shop-theme', nextTheme);
        } catch (error) {
            // The chosen theme remains active for this page if storage is unavailable.
        }
        updateButton();
    });
})();
