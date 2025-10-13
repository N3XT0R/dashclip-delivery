import './bootstrap';
import ZipDownloader from './components/ZipDownloader';

window.clipSelector = function clipSelector({ startModel, endModel, durationModel }) {
    return {
        startModel,
        endModel,
        durationModel,
        sliderInstance: null,
        videoEl: null,
        sliderEl: null,
        videoSource: null,
        pond: null,
        hasVideo: false,
        formattedStart: '00:00',
        formattedEnd: '00:00',

        async init() {
            this.videoEl = this.$refs.video;
            this.sliderEl = this.$refs.slider;

            this.updateFormattedTimes();

            this.$watch('startModel', () => this.syncSliderFromModels());
            this.$watch('endModel', () => this.syncSliderFromModels());
            this.$watch('durationModel', () => this.syncSliderFromModels());

            await this.connectToFilePond();

            if (this.durationModel) {
                this.prepareSlider();
            }
        },

        async connectToFilePond() {
            const repeaterItem = this.$root.closest('.fi-fo-repeater-item');

            if (!repeaterItem) {
                return;
            }

            const fileInput = repeaterItem.querySelector('.fi-fo-file-upload input[type="file"]');

            if (!fileInput) {
                return;
            }

            const pond = await this.waitForPondInstance(fileInput);

            if (!pond) {
                return;
            }

            this.pond = pond;

            pond.on('addfile', (error, file) => {
                if (error) {
                    return;
                }

                this.loadFile(file?.file ?? null);
            });

            pond.on('removefile', () => {
                this.reset();
            });

            const existing = pond
                .getFiles()
                .find((candidate) => candidate?.file instanceof File);

            if (existing) {
                this.loadFile(existing.file);
            }
        },

        waitForPondInstance(input) {
            return new Promise((resolve) => {
                const lookup = () => {
                    if (window.FilePond) {
                        const instance = window.FilePond.find(input);

                        if (instance) {
                            resolve(instance);

                            return;
                        }
                    }

                    requestAnimationFrame(lookup);
                };

                lookup();
            });
        },

        loadFile(file) {
            if (!(file instanceof File)) {
                return;
            }

            this.hasVideo = true;

            if (this.videoSource) {
                URL.revokeObjectURL(this.videoSource);
                this.videoSource = null;
            }

            this.videoSource = URL.createObjectURL(file);
            this.videoEl.src = this.videoSource;
            this.videoEl.load();

            this.videoEl.addEventListener(
                'loadedmetadata',
                () => {
                    const duration = Number.isFinite(this.videoEl.duration)
                        ? Math.max(0, Math.round(this.videoEl.duration))
                        : 0;

                    this.durationModel = duration || this.durationModel || 0;

                    if (this.startModel == null || this.startModel < 0) {
                        this.startModel = 0;
                    }

                    if (this.endModel == null || this.endModel > duration) {
                        this.endModel = duration;
                    }

                    this.prepareSlider();
                    this.updateFormattedTimes();
                },
                { once: true },
            );
        },

        prepareSlider() {
            if (!this.sliderEl) {
                return;
            }

            const duration = Number(this.durationModel ?? 0);
            const fallbackExtent = Math.max(
                Math.round(Number(this.startModel ?? 0)),
                Math.round(Number(this.endModel ?? 0)),
                1,
            );
            const safeDuration = Number.isFinite(duration) && duration > 0 ? duration : fallbackExtent;
            const startValue = Math.max(0, Math.min(safeDuration, Number(this.startModel ?? 0)));
            const endValue = Math.max(startValue, Math.min(safeDuration, Number(this.endModel ?? safeDuration)));

            if (this.sliderInstance) {
                this.sliderInstance.destroy();
            }

            const sliderLib = window.noUiSlider;

            if (!sliderLib) {
                return;
            }

            this.sliderInstance = sliderLib.create(this.sliderEl, {
                start: [startValue, endValue],
                connect: true,
                behaviour: 'tap-drag',
                step: 1,
                range: {
                    min: 0,
                    max: safeDuration,
                },
            });

            this.sliderInstance.on('update', (values) => {
                const [start, end] = values.map((value) => Math.max(0, Math.round(Number(value) || 0)));

                if (start !== Math.round(Number(this.startModel ?? 0))) {
                    this.startModel = start;
                }

                if (end !== Math.round(Number(this.endModel ?? 0))) {
                    this.endModel = end;
                }

                this.updateFormattedTimes();
            });
        },

        syncSliderFromModels() {
            this.updateFormattedTimes();

            if (!this.sliderInstance) {
                return;
            }

            const current = this.sliderInstance
                .get()
                .map((value) => Math.round(Number(value) || 0));
            const target = [
                Math.max(0, Math.round(Number(this.startModel ?? 0))),
                Math.max(0, Math.round(Number(this.endModel ?? 0))),
            ];

            if (current[0] !== target[0] || current[1] !== target[1]) {
                this.sliderInstance.set(target);
            }
        },

        updateFormattedTimes() {
            this.formattedStart = this.formatTime(this.startModel);
            const endBase = this.endModel ?? this.durationModel ?? 0;
            this.formattedEnd = this.formatTime(endBase);
        },

        formatTime(value) {
            const seconds = Math.max(0, Math.round(Number(value) || 0));
            const minutes = Math.floor(seconds / 60)
                .toString()
                .padStart(2, '0');
            const secs = (seconds % 60).toString().padStart(2, '0');

            return `${minutes}:${secs}`;
        },

        reset() {
            this.hasVideo = false;

            if (this.sliderInstance) {
                this.sliderInstance.destroy();
                this.sliderInstance = null;
            }

            if (this.videoSource) {
                URL.revokeObjectURL(this.videoSource);
                this.videoSource = null;
            }

            this.startModel = 0;
            this.endModel = null;
            this.durationModel = null;

            this.updateFormattedTimes();
        },

        destroy() {
            if (this.sliderInstance) {
                this.sliderInstance.destroy();
                this.sliderInstance = null;
            }

            if (this.videoSource) {
                URL.revokeObjectURL(this.videoSource);
                this.videoSource = null;
            }
        },
    };
};


document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zipForm');
    if (form) {
        new ZipDownloader(form);
    }
});
