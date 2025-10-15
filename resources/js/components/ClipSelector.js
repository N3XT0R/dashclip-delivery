const clampToSeconds = (value) => {
    if (Number.isNaN(value) || value === undefined || value === null) {
        return 0;
    }

    const floored = Math.floor(Number(value));

    if (!Number.isFinite(floored) || floored < 0) {
        return 0;
    }

    return floored;
};

const findFilePondRoot = (element) => {
    if (!element) {
        return null;
    }

    const searchSiblings = (node) => {
        let current = node?.previousElementSibling ?? null;
        while (current) {
            if (typeof current.querySelector === 'function') {
                const candidate = current.querySelector('.filepond--root');
                if (candidate) {
                    return candidate;
                }
            }
            current = current.previousElementSibling ?? null;
        }

        return null;
    };

    let root = searchSiblings(element);
    if (root) {
        return root;
    }

    let parent = element.parentElement;
    while (parent) {
        root = searchSiblings(parent);
        if (root) {
            return root;
        }
        if (typeof parent.querySelector === 'function') {
            const candidate = parent.querySelector('.filepond--root');
            if (candidate) {
                return candidate;
            }
        }
        parent = parent.parentElement;
    }

    return null;
};

const registerClipSelector = () => {
    if (window.clipSelector) {
        return;
    }

    window.clipSelector = () => ({
        start: 0,
        end: 0,
        duration: 0,
        showPlayer: false,
        fileUrl: null,
        pondRoot: null,
        pondListeners: {},
        metadataListeners: {},

        init() {
            this.duration = clampToSeconds(this.duration);
            this.start = clampToSeconds(this.start);
            this.end = clampToSeconds(this.end);

            this.setupVideoListeners();
            this.observeFilePond();
        },

        destroyed() {
            this.teardownFilePond();
            this.releaseVideo();
            this.removeVideoListeners();
        },

        setupVideoListeners() {
            const video = this.$refs.video;
            if (!video) {
                return;
            }

            const onLoadedMetadata = () => {
                this.updateDuration(video.duration ?? 0);
            };

            const onError = () => {
                this.updateDuration(0);
            };

            video.addEventListener('loadedmetadata', onLoadedMetadata);
            video.addEventListener('error', onError);

            this.metadataListeners.loadedmetadata = onLoadedMetadata;
            this.metadataListeners.error = onError;
        },

        removeVideoListeners() {
            const video = this.$refs.video;
            if (!video) {
                return;
            }

            Object.entries(this.metadataListeners).forEach(([event, listener]) => {
                video.removeEventListener(event, listener);
            });
            this.metadataListeners = {};
        },

        observeFilePond() {
            const attemptAttach = (retries = 0) => {
                this.pondRoot = findFilePondRoot(this.$el);
                if (!this.pondRoot) {
                    if (retries < 10) {
                        window.setTimeout(() => attemptAttach(retries + 1), 150);
                    }
                    return;
                }

                this.attachFilePondListeners();
                this.syncInitialFile();
            };

            attemptAttach();
        },

        attachFilePondListeners() {
            if (!this.pondRoot) {
                return;
            }

            const handleAddFile = (event) => {
                const file = event?.detail?.file?.file ?? event?.detail?.file?.source ?? null;
                if (file instanceof Blob) {
                    this.loadVideo(file);
                }
            };

            const handleRemoveFile = () => {
                this.resetState();
            };

            this.pondRoot.addEventListener('FilePond:addfile', handleAddFile);
            this.pondRoot.addEventListener('FilePond:removefile', handleRemoveFile);

            this.pondListeners.addfile = handleAddFile;
            this.pondListeners.removefile = handleRemoveFile;
        },

        teardownFilePond() {
            if (!this.pondRoot) {
                return;
            }

            Object.entries(this.pondListeners).forEach(([event, listener]) => {
                this.pondRoot.removeEventListener(`FilePond:${event}`, listener);
            });

            this.pondListeners = {};
            this.pondRoot = null;
        },

        syncInitialFile() {
            const pondInstance = this.pondRoot?.filepond ?? null;
            if (!pondInstance) {
                return;
            }

            const files = pondInstance.getFiles();
            if (!files?.length) {
                return;
            }

            const initial = files[0]?.file ?? null;
            if (initial instanceof Blob) {
                this.loadVideo(initial);
            }
        },

        loadVideo(file) {
            const video = this.$refs.video;
            if (!video) {
                return;
            }

            this.releaseVideo();

            const url = window.URL.createObjectURL(file);
            this.fileUrl = url;
            video.src = url;
            video.load();
        },

        releaseVideo() {
            if (this.fileUrl) {
                window.URL.revokeObjectURL(this.fileUrl);
                this.fileUrl = null;
            }
        },

        updateDuration(rawDuration) {
            const seconds = clampToSeconds(rawDuration);
            this.duration = seconds;

            if (this.end <= 0 || this.end > seconds) {
                this.end = seconds;
            }

            if (this.start >= seconds) {
                this.start = 0;
            }
        },

        resetState() {
            this.releaseVideo();
            this.showPlayer = false;
            this.updateDuration(0);
            this.start = 0;
            this.end = 0;
            const video = this.$refs.video;
            if (video) {
                video.removeAttribute('src');
                video.load();
            }
        },
    });
};

export default registerClipSelector;
