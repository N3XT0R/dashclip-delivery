import './bootstrap';
import 'nouislider/dist/nouislider.css';
import ZipDownloader from './components/ZipDownloader';

window.noUiSlider = noUiSlider;
window.clipSelector = clipSelector;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zipForm');
    if (form) {
        new ZipDownloader(form);
    }
});
