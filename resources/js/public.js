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

document.querySelectorAll('[data-youtube-embed]').forEach(container => {
    container.querySelector('[data-youtube-load]')?.addEventListener('click', () => {
        const frame = document.createElement('iframe');
        frame.src = container.dataset.youtubeEmbed;
        frame.title = container.dataset.youtubeTitle;
        frame.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        frame.allowFullscreen = true;
        frame.referrerPolicy = 'strict-origin-when-cross-origin';
        frame.className = 'absolute inset-0 h-full w-full';
        container.replaceChildren(frame);
        frame.focus();
    });
});
