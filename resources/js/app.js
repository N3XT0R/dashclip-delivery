import './bootstrap';
import ZipDownloader from './components/ZipDownloader';
import {run} from 'cookieconsent';
import 'cookieconsent/build/cookieconsent.min.css';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zipForm');
    if (form) {
        new ZipDownloader(form);
    }
});

document.addEventListener("DOMContentLoaded", () => {
    run({
        categories: {
            necessary: {
                enabled: true,
                readOnly: true
            }
        },
        language: {
            default: 'de',
            translations: {
                de: {
                    consentModal: {
                        title: "Cookies",
                        description: "Wir verwenden ausschließlich technisch notwendige Cookies.",
                        acceptAllBtn: "OK",
                        closeIconLabel: "Schließen"
                    },
                    preferencesModal: {
                        title: "Cookie-Einstellungen",
                        acceptNecessaryBtn: "Nur notwendige Cookies",
                        acceptAllBtn: "Alle akzeptieren"
                    }
                }
            }
        }
    });
});
