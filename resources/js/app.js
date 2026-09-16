import './bootstrap';
import ZipDownloader from './components/ZipDownloader';
import 'cookieconsent/build/cookieconsent.min.css';

const downloaders = new WeakMap();
function downloaderFor(form) {
    if (!downloaders.has(form)) downloaders.set(form, new ZipDownloader(form));
    return downloaders.get(form);
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zipForm');
    if (form) {
        downloaderFor(form);
    }
});

document.addEventListener('livewire:init', () => {
    Livewire.on('zip-download', payload => {
        const params = payload?.[0] ?? {};
        const ids = params.assignmentIds ?? [];
        if (!ids.length) return;

        const form = document.getElementById('zipForm');
        if (!form) {
            console.warn('zipForm not found');
            return;
        }

        downloaderFor(form).startDownload(ids);
    });
});
