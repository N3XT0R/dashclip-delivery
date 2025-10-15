<div>
    <input
            type="hidden"
            value="0"
            wire:model.defer="{{ $getStatePath() }}.duration"
    >
</div>
<script>
    document.addEventListener('FilePond:addfile', (event) => {
        const modelPath = '{{ $getStatePath() }}.duration';
        const selector = `input[wire\\:model\\.defer="${modelPath}"]`;
        const input = document.querySelector(selector);
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
            URL.revokeObjectURL(url);
            input.value = Math.floor(video.duration ?? 0);
            console.log(input.value);
            input.dispatchEvent(new Event('input', {bubbles: true}));
        };
    });
</script>