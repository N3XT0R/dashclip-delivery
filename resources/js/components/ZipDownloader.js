import axios from 'axios';
import DownloadModal from './DownloadModal';

export default class ZipDownloader {
    constructor(arg) {
        this.form = arg instanceof HTMLFormElement ? arg : arg.form;
        this.modal = arg.modal ?? new DownloadModal();
        this.storageKey = `offer-download:${new URL(this.form.dataset.zipPostUrl, window.location.href).pathname}`;
        this.generation = 0;
        this.running = false;
        this.modal.onClose(() => {
            this.generation++;
            this.running = false;
            // a closed dialog is done with, so a reload must not bring it back
            this.forget();
        });
        this.modal.onRetry(() => this.startDownload(this.selected));
        this.init();
        this.restore();
    }

    init() {
        this.updateCount();
        document.getElementById('selectAll')?.addEventListener('click', () => this.toggleAll(true));
        document.getElementById('selectNone')?.addEventListener('click', () => this.toggleAll(false));
        document.addEventListener('change', e => {
            if (e.target?.classList?.contains('pickbox')) this.updateCount();
        });
        document.getElementById('zipSubmit')?.addEventListener('click', e => {
            e.preventDefault();
            this.startDownload();
        });
        document.querySelectorAll('.single-download').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                if (btn.dataset.assignmentId) this.startDownload([btn.dataset.assignmentId]);
            });
        });
    }

    toggleAll(state) {
        document.querySelectorAll('.pickbox:not(:disabled)').forEach(cb => cb.checked = state);
        this.updateCount();
    }

    updateCount() {
        const element = document.getElementById('selCount');
        if (element) element.textContent = `${document.querySelectorAll('.pickbox:checked').length} ausgewählt`;
    }

    async startDownload(forcedIds = null) {
        if (this.running) {
            this.modal.show();
            return;
        }
        this.selected = forcedIds || Array.from(document.querySelectorAll('.pickbox:checked'), cb => cb.value);
        if (!this.selected.length) return;
        const generation = ++this.generation;
        this.running = true;
        this.modal.open();
        try {
            const {data} = await axios.post(this.form.dataset.zipPostUrl, {
                assignment_ids: this.selected,
            }, {
                timeout: 30000,
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content},
            });
            if (generation !== this.generation) return;
            this.save(data);
            this.modal.setDownloads(data.downloads);
            await this.poll(data, generation);
        } catch (error) {
            if (generation !== this.generation) return;
            this.running = false;
            const status = error.response?.status;
            this.modal.showError(status === 403
                ? 'Der Link ist abgelaufen oder ungültig. Bitte die Angebotsseite neu öffnen.'
                : status === 422 ? 'Die Auswahl ist nicht mehr verfügbar. Bitte die Angebotsseite aktualisieren.'
                    : 'Der Download konnte nicht vorbereitet werden. Bitte erneut versuchen.');
        }
    }

    async poll(data, generation) {
        const deadline = Date.now() + 25 * 60 * 1000;
        let failures = 0;
        if (data.status === 'failed') {
            this.running = false;
            this.modal.showError('Die ZIP-Erstellung ist fehlgeschlagen. Die Videos können einzeln heruntergeladen werden.');
            return;
        }
        while (generation === this.generation && Date.now() < deadline) {
            try {
                const {data: state} = await axios.get(data.progressUrl, {timeout: 15000});
                if (generation !== this.generation) return;
                failures = 0;
                this.modal.update(state.progress, state.status, state.files);
                if (state.status === 'ready') {
                    this.modal.setDownloads([{name: state.name || 'ZIP herunterladen', url: data.downloadUrl}, ...data.downloads]);
                    this.save({...data, status: 'ready', name: state.name});
                    this.deliver(data.downloadUrl);
                    const skipped = Object.values(state.files || {}).filter(status => status === 'skipped').length;
                    this.modal.update(100, skipped
                        ? `ZIP bereit. ${skipped} nicht verfügbare${skipped === 1 ? 's Video wurde' : ' Videos wurden'} übersprungen. Der Link bleibt für einen erneuten Versuch verfügbar.`
                        : 'ZIP bereit. Der Download wurde an den Browser übergeben. Der Link bleibt für einen erneuten Versuch verfügbar.');
                    this.running = false;
                    return;
                }
                if (['failed', 'unknown'].includes(state.status)) {
                    this.running = false;
                    this.modal.showError('Die ZIP ist nicht verfügbar. Bitte einzeln herunterladen oder die ZIP erneut erstellen.');
                    return;
                }
            } catch (error) {
                if (generation !== this.generation) return;
                if ([403, 404, 410].includes(error.response?.status)) break;
                failures++;
                this.modal.update(0, 'Verbindung unterbrochen. Der Status wird erneut abgefragt; Einzel-Downloads bleiben verfügbar.');
            }
            await new Promise(resolve => setTimeout(resolve, Math.min(2000 * 2 ** failures, 15000)));
        }
        if (generation !== this.generation) return;
        this.running = false;
        this.modal.showError('Die ZIP ist noch nicht verfügbar. Bitte einzeln herunterladen oder später erneut versuchen.');
    }

    deliver(url) {
        if (!this.frame) {
            this.frame = document.createElement('iframe');
            this.frame.hidden = true;
            this.frame.title = 'Dateidownload';
            this.frame.addEventListener('load', () => {
                try {
                    if (this.frame.contentDocument?.body?.textContent?.trim()) {
                        this.modal.showError('Die Datei konnte nicht abgerufen werden. Bitte den Download-Link öffnen oder erneut versuchen.');
                    }
                } catch {
                    this.modal.showError('Bitte den Download-Link öffnen, um den Abruf erneut zu versuchen.');
                }
            });
            document.body.appendChild(this.frame);
        }
        this.frame.src = url;
    }

    save(data) {
        try {
            sessionStorage.setItem(this.storageKey, JSON.stringify({data, selected: this.selected, savedAt: Date.now()}));
        } catch { /* Downloads also work when browser storage is unavailable. */ }
    }

    forget() {
        try {
            sessionStorage.removeItem(this.storageKey);
        } catch { /* Nothing to forget when browser storage is unavailable. */ }
    }

    restore() {
        try {
            const saved = JSON.parse(sessionStorage.getItem(this.storageKey));
            if (!saved || Date.now() - saved.savedAt > 24 * 60 * 60 * 1000) return;
            this.selected = saved.selected;
            this.modal.open();
            const data = saved.data;
            this.modal.setDownloads(data.status === 'ready' && data.jobId
                ? [{name: data.name || 'ZIP herunterladen', url: data.downloadUrl}, ...data.downloads]
                : data.downloads);
            if (data.jobId && data.status !== 'ready') {
                this.running = true;
                this.poll(data, ++this.generation);
            } else {
                this.modal.update(100, 'Die Download-Links stehen weiterhin bereit.');
            }
        } catch { /* Ignore expired or unavailable session state. */ }
    }
}
