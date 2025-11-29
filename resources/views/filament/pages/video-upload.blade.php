<x-filament-panels::page>
    <form wire:submit.prevent="submit" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit" id="submit">
            Upload
        </x-filament::button>
    </form>
</x-filament-panels::page>

<script>
    const submit = document.getElementById('submit');
    document.addEventListener('FilePond:processfilestart', () => toggleButton(submit, true));
    document.addEventListener('FilePond:processfileprogress', function (e) {
        let detail = e.detail;
        if (detail.progress === 1) {
            toggleButton(submit, false);
        } else {
            toggleButton(submit, true);
        }
    });
    document.addEventListener('FilePond:processfile', () => toggleButton(submit, false));
    document.addEventListener('FilePond:processfileabort', () => toggleButton(submit, false));
    document.addEventListener('FilePond:error', () => toggleButton(submit, false));


    function toggleButton(button, disabled) {
        button.disabled = disabled;
    }
</script>
