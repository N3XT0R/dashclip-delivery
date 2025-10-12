import './bootstrap';
import noUiSlider from 'nouislider';
import 'nouislider/dist/nouislider.css';
import ZipDownloader from './components/ZipDownloader';
import clipSelector from './components/clip-selector.js';

window.noUiSlider = noUiSlider;
window.clipSelector = clipSelector;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zipForm');
    if (form) {
        new ZipDownloader(form);
    }
});
