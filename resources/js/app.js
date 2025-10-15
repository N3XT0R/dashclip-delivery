import './bootstrap';
import ZipDownloader from './components/ZipDownloader';
import registerClipSelector from './components/ClipSelector';

registerClipSelector();


document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zipForm');
    if (form) {
        new ZipDownloader(form);
    }
});
