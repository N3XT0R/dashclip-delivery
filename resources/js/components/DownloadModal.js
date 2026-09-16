export default class DownloadModal {
    constructor(options = {}) {
        this.options = {
            overlayBackground: options.overlayBackground ?? 'rgba(0,0,0,0.5)',
            panelBackground: options.panelBackground ?? '#111827',
            panelTextColor: options.panelTextColor ?? '#f9fafb',
            panelShadow: options.panelShadow ?? '0 10px 30px rgba(0,0,0,0.4)',
        };
        this.modal = document.createElement('div');
        this.modal.id = 'downloadModal';
        this.modal.setAttribute('role', 'region');
        this.modal.setAttribute('aria-label', 'Downloadfortschritt');
        this.modal.style.cssText = `
            display:none;
            position:fixed;
            inset:0;
            background:${this.options.overlayBackground};
            align-items:center;
            justify-content:center;
            z-index:50;
        `;
        this.modal.innerHTML = `
            <div
              class="panel"
              style="
                max-width:600px;
                width:90%;
                max-height:90dvh;
                overflow:auto;
                padding:24px;
                border-radius:12px;
                background:${this.options.panelBackground};
                color:${this.options.panelTextColor};
              "
            >
                <h3>Deine Downloads</h3>
                <table class="w-full my-3 text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Video</th>
                            <th class="text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody id="downloadFileList"></tbody>
                </table>
                <p id="statusText" class="text-sm mb-2" role="status" aria-live="polite"></p>
                <div class="w-full h-2 bg-gray-200 rounded overflow-hidden">
                    <div id="zipProgressBar" class="h-full w-0 bg-blue-500 transition-all" role="progressbar" aria-label="ZIP-Download" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                </div>
                <p id="progressText" class="text-right text-sm mt-1">0%</p>
                <div id="downloadLinks" style="display:grid;gap:10px;max-height:35vh;overflow:auto;margin-top:16px"></div>
                <button type="button" id="retryDownload" class="btn mt-4" hidden>Erneut versuchen</button>
                <button type="button" id="closeModal" class="btn mt-4">Schließen</button>
            </div>`;
        document.body.appendChild(this.modal);
        this.fileList = this.modal.querySelector('#downloadFileList');
        this.progressBar = this.modal.querySelector('#zipProgressBar');
        this.progressText = this.modal.querySelector('#progressText');
        this.statusText = this.modal.querySelector('#statusText');
        this.closeBtn = this.modal.querySelector('#closeModal');
        this.links = this.modal.querySelector('#downloadLinks');
        this.retryBtn = this.modal.querySelector('#retryDownload');

        this.messages = {
            queued: 'Wartet...',
            preparing: 'Bereite Dateien vor...',
            downloading: 'Wird heruntergeladen...',
            downloaded: 'Heruntergeladen',
            packing: 'Wird gepackt...',
            ready: 'Fertig'
        };
    }

    open(files = []) {
        this.fileList.innerHTML = '';
        files.forEach(name => {
            const tr = document.createElement('tr');
            tr.dataset.name = name;
            const tdName = document.createElement('td');
            tdName.textContent = name;
            const tdStatus = document.createElement('td');
            tdStatus.textContent = this.messages.queued;
            tdStatus.classList.add('status');
            tr.appendChild(tdName);
            tr.appendChild(tdStatus);
            this.fileList.appendChild(tr);
        });
        this.progressBar.style.width = '0%';
        this.progressBar.setAttribute('aria-valuenow', '0');
        this.progressText.textContent = '0%';
        this.statusText.textContent = this.messages.queued;
        this.links.replaceChildren();
        this.retryBtn.hidden = true;
        this.modal.style.display = 'flex';
    }

    update(progress, status, files = {}) {
        this.progressBar.style.width = `${progress}%`;
        this.progressBar.setAttribute('aria-valuenow', String(progress));
        this.progressText.textContent = `${progress}%`;
        if (status) {
            const msg = this.messages[status] || status;
            this.statusText.textContent = msg;
        }
        Object.entries(files).forEach(([name, st]) => {
            let row = Array.from(this.fileList.children).find(row => row.dataset.name === name);
            if (!row) {
                row = document.createElement('tr');
                row.dataset.name = name;
                const tdName = document.createElement('td');
                tdName.textContent = name;
                const tdStatus = document.createElement('td');
                tdStatus.classList.add('status');
                row.appendChild(tdName);
                row.appendChild(tdStatus);
                this.fileList.appendChild(row);
            }
            const tdStatus = row.querySelector('.status');
            tdStatus.textContent = this.messages[st] || st;
        });
    }

    showClose() {
        this.closeBtn.classList.remove('hidden');
    }

    show() {
        this.modal.style.display = 'flex';
    }

    setDownloads(downloads = []) {
        this.links.replaceChildren();
        downloads.forEach(({name, url}) => {
            const link = document.createElement('a');
            link.href = url;
            link.textContent = name;
            link.target = '_blank';
            link.rel = 'noopener';
            link.style.cssText = 'text-decoration:underline;overflow-wrap:anywhere';
            this.links.appendChild(link);
        });
    }

    showError(message) {
        this.statusText.textContent = message;
        this.retryBtn.hidden = false;
    }

    onRetry(callback) {
        this.retryBtn.addEventListener('click', callback);
    }

    onClose(cb) {
        this.closeBtn.addEventListener('click', () => {
            this.modal.style.display = 'none';
            if (cb) cb();
        });
    }
}
