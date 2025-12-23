import './bootstrap';
import ZipDownloader from './components/ZipDownloader';
import 'cookieconsent/build/cookieconsent.min.css';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zipForm');
    if (form) {
        new ZipDownloader(form);
    }
});

document.addEventListener('livewire:init', () => {
    Livewire.on('zip-download', payload => {
        const ids = payload?.assignmentIds ?? [];
        if (!ids.length) return;

        const form = document.getElementById('zipForm');
        if (!form) {
            console.warn('zipForm not found');
            return;
        }

        const downloader = new ZipDownloader(form);
        downloader.startDownload(ids);
    });
});
