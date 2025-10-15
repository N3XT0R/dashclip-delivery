<div>
    <input
            type="hidden"
            value="0"
            wire:model.defer="{{ $getStatePath() }}.duration"
    >
</div>
<script>
    document.addEventListener('FilePond:addfile', (event) => {
        console.log(event.target);
        const file = event.detail.file.file;
        const url = URL.createObjectURL(file);
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.src = url;
        video.onloadedmetadata = () => {
            URL.revokeObjectURL(url);
            console.log('Dauer:', video.duration);
        };
    });
</script>