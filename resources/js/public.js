const themeToggle = document.getElementById('themeToggle');
document.querySelectorAll('[data-copy-link]').forEach(button => {
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(button.dataset.copyLink);
            button.textContent = button.dataset.copiedLabel;
            document.querySelector('[data-copy-status]').textContent = button.dataset.copiedLabel;
        } catch {
            window.prompt(button.textContent, button.dataset.copyLink);
        }
    });
});
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

const cookieBanner = document.getElementById('cookie-banner');
const cookieSpacer = document.createElement('div');
cookieSpacer.setAttribute('aria-hidden', 'true');
const cookieResizeObserver = new ResizeObserver(() => {
    cookieSpacer.style.height = `${cookieBanner.getBoundingClientRect().height}px`;
});
if (cookieBanner) {
    cookieBanner.before(cookieSpacer);
    cookieResizeObserver.observe(cookieBanner);
}

document.getElementById('cookie-accept')?.addEventListener('click', () => {
    const expires = new Date();
    expires.setFullYear(expires.getFullYear() + 1);
    document.cookie = `cookie_consent=true; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
    cookieResizeObserver.disconnect();
    cookieBanner?.remove();
    cookieSpacer.remove();
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
