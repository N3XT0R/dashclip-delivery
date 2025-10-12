export default function clipSelector() {
    return {
        start: 0,
        end: 0,
        duration: 0,
        showPlayer: false,

        bindInput(input) {
            input.addEventListener('change', () => {
                const file = input.files[0];
                if (!file) return;

                this.showPlayer = false;
                this.$refs.video.src = URL.createObjectURL(file);

                this.$refs.video.addEventListener('loadedmetadata', () => {
                    this.duration = this.$refs.video.duration;
                    this.end = this.duration;

                    // init slider erst, wenn Video vollständig bekannt ist
                    this.$nextTick(() => this.initSlider());
                    this.showPlayer = true;
                });
            });
        },

        initSlider() {
            // Falls bereits vorhanden, zerstören
            if (this.$refs.slider.noUiSlider) {
                this.$refs.slider.noUiSlider.destroy();
            }

            if (!window.noUiSlider || !this.$refs.slider) {
                console.warn('noUiSlider oder Slider-Ref fehlt');
                return;
            }

            // Jetzt sicher initialisieren
            noUiSlider.create(this.$refs.slider, {
                start: [this.start, this.end],
                connect: true,
                range: {min: 0, max: this.duration},
                step: 1,
            });

            // Live-Update binden
            this.$refs.slider.noUiSlider.on('update', (values) => {
                this.start = Math.round(values[0]);
                this.end = Math.round(values[1]);
            });
        },

        init() {
            const input = this.$el.querySelector('input[type=file]');
            if (input) this.bindInput(input);
        },
    };
}
