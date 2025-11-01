import './bootstrap';
import ZipDownloader from './components/ZipDownloader';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zipForm');
    if (form) {
        new ZipDownloader(form);
    }
});

document.addEventListener('click', e => {
    const video = e.target.closest('video');
    if (video && !video.src) {
        video.src = video.dataset.src;
        video.load();
        video.play().catch(() => {
        });
    }
});