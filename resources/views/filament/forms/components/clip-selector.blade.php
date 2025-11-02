<div>
    <input
            type="hidden"
            value="0"
            wire:model.defer="{{ $getStatePath() }}.duration"
    >
</div>
<script>
    function formatDuration(value) {
        const seconds = Number(value);

        if (!Number.isFinite(seconds)) return "00:00";

        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    document.addEventListener('FilePond:addfile', (event) => {
        const modelPath = '{{ $getStatePath() }}.duration';
        const selector = `input[wire\\:model\\.defer="${modelPath}"]`;
        const input = document.querySelector(selector);

        const endSec = '{{ $getStatePath() }}.end_sec';
        const endSelector = `input[wire\\:model="${endSec}"]`;
        const endInput = document.querySelector(endSelector);

        if (!input) {
            return;
        }

        const file = event.detail?.file?.file;
        if (!file) {
            return;
        }

        const url = URL.createObjectURL(file);
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.src = url;
        video.onloadedmetadata = () => {
            let duration = Math.floor(video.duration ?? 0);
            URL.revokeObjectURL(url);
            input.value = duration;

            input.dispatchEvent(new Event('input', {bubbles: true}));
            if (endInput) {
                let formatted = formatDuration(duration);
                endInput.value = formatDuration(formatted);
                endInput.dispatchEvent(new Event('input', {bubbles: true}));
            }
        };
    });
</script>