import './bootstrap';
import ZipDownloader from './components/ZipDownloader';
import DownloadModal from './components/DownloadModal';
import 'cookieconsent/build/cookieconsent.min.css';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zipForm');
    if (form) {
        new ZipDownloader(form);
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

        let modal = new DownloadModal({
            overlayBackground: 'transparent',
            panelBackground: '#ffffff',
            panelTextColor: '#111827',
        })

        const downloader = new ZipDownloader({
            form: form,
            modal: modal,
        });
        downloader.startDownload(ids);
    });
});
