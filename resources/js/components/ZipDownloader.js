import axios from 'axios';
import DownloadModal from './DownloadModal';

/**
 * hating js for being js, backend development would be so much easier without this mess
 */
export default class ZipDownloader {
    constructor(arg) {
        if (arg instanceof HTMLFormElement) {
            this.form = arg;
            this.modal = new DownloadModal(); // legacy default
        } else {
            this.form = arg.form;
            this.modal = arg.modal ?? new DownloadModal();
        }
        this.selectAllBtn = document.getElementById('selectAll');
        this.selectNoneBtn = document.getElementById('selectNone');
        this.submitBtn = document.getElementById('zipSubmit');
        this.selCountEl = document.getElementById('selCount');

        this.modal = new DownloadModal();
        this.modal.onClose(() => {
        });

        this.init();
    }

    sanitizeName(name) {
        return name.replace(/[\\/:*?"<>|]+/g, '_');
    }

    init() {
        this.updateCount();
        this.selectAllBtn?.addEventListener('click', () => this.toggleAll(true));
        this.selectNoneBtn?.addEventListener('click', () => this.toggleAll(false));
        document.addEventListener('change', e => {
            if (e.target && e.target.classList?.contains('pickbox')) {
                this.updateCount();
            }
        });
        this.submitBtn?.addEventListener('click', () => this.startDownload());
        // Einzel-Download-Buttons
        document.querySelectorAll('.single-download').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.assignmentId;
                if (!id) return;
                await this.startDownload([id]);
            });
        });
    }

    toggleAll(state) {
        document.querySelectorAll('.pickbox').forEach(cb => cb.checked = state);
        this.updateCount();
    }

    updateCount() {
        const n = document.querySelectorAll('.pickbox:checked').length;
        if (this.selCountEl) this.selCountEl.textContent = `${n} ausgewählt`;
    }

    async startDownload(forcedIds = null) {
        const boxes = Array.from(document.querySelectorAll('.pickbox:checked'));
        const selected = forcedIds || boxes.map(cb => cb.value);
        if (!selected.length) {
            alert('Bitte wähle mindestens ein Video aus.');
            return;
        }

        const files = boxes
            .map(cb => cb.closest('.card')?.querySelector('.file-name')?.textContent?.trim())
            .filter(Boolean)
            .map(name => this.sanitizeName(name));
        this.modal.open(files);

        const postUrl = this.form.dataset.zipPostUrl;
        const token = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute('content');
        const {
            data: {jobId}
        } = await axios.post(
            postUrl,
            {assignment_ids: selected},
            {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            }
        );

        let downloading = false;
        const channelName = `zip.${jobId}`;
        window.Echo.channel(channelName).listen('.zip.progress', async r => {
            if (r.status === 'ready' && !downloading) {
                downloading = true;
                this.modal.update(r.progress || 0, null, r.files || {});
                await this.downloadZip(jobId, r.name);
                window.Echo.leave(channelName);
            } else {
                this.modal.update(r.progress || 0, r.status, r.files || {});
            }
        });
    }

    async downloadZip(jobId, filename) {

        const response = await axios.get(`/zips/${jobId}/download`, {
            responseType: 'blob'
        });
        if (response.status === 200) {
            this.modal.update(100, 'ready');
            this.modal.showClose();
            window.location.reload();
        }
        const blob = new Blob([response.data], {type: 'application/zip'});
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', filename || `download-${jobId}.zip`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

    }
}
