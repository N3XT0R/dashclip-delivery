import './bootstrap';
import ZipDownloader from './components/ZipDownloader';

const form = document.getElementById('zipForm');
if (form) {
    new ZipDownloader(form);
}
