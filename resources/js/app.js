import './bootstrap';
import ZipDownloader from './components/ZipDownloader';
import 'cookieconsent/build/cookieconsent.min.css';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zipForm');
    if (form) {
        new ZipDownloader(form);
    }
});
