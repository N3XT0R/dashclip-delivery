<div>
    <input
            type="hidden"
            value="0"
            wire:model.defer="{{ $getStatePath() }}.duration"
    >
</div>

<script>
    document.addEventListener('FilePond:addfile', (event) => {
        const modelBase = @json($getStatePath());
        const file = event.detail?.file?.file;
        if (!file) return;

        const url = URL.createObjectURL(file);
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.src = url;

        video.onloadedmetadata = () => {
            const duration = Math.floor(video.duration ?? 0);
            URL.revokeObjectURL(url);

            const component = window.Livewire.find(event.target.closest('[wire\\:id]').getAttribute('wire:id'));
            if (component) {
                component.set(`${modelBase}.duration`, duration);
                
                const mins = Math.floor(duration / 60);
                const secs = duration % 60;
                const formatted = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

                component.set(`${modelBase}.end_sec`, formatted);
            }
        };
    });
</script>
