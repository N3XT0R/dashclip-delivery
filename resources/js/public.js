const themeToggle = document.getElementById('themeToggle');
const updateThemeControl = () => {
    themeToggle?.setAttribute('aria-pressed', String(document.documentElement.classList.contains('dark')));
};
updateThemeControl();
themeToggle?.addEventListener('click', () => {
    const dark = document.documentElement.classList.toggle('dark');
    updateThemeControl();
    try {
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    } catch {}
});

document.getElementById('cookie-accept')?.addEventListener('click', () => {
    const expires = new Date();
    expires.setFullYear(expires.getFullYear() + 1);
    document.cookie = `cookie_consent=true; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
    document.getElementById('cookie-banner')?.remove();
});

const mobileNavigation = document.getElementById('mobile-navigation');
mobileNavigation?.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        mobileNavigation.open = false;
        mobileNavigation.querySelector('summary').focus();
    }
});
mobileNavigation?.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => { mobileNavigation.open = false; });
});
