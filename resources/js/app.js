import './bootstrap';
import ZipDownloader from './components/ZipDownloader';
import 'cookieconsent/build/cookieconsent.min.css';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zipForm');
    if (form) {
        new ZipDownloader(form);
    }
});

document.addEventListener('zip-download', e => {
    const ids = e.detail.assignmentIds ?? [];
    if (!ids.length) return;

    const form = document.getElementById('zipForm');
    if (!form) return;

    const downloader = new ZipDownloader(form);
    downloader.startDownload(ids);
});
