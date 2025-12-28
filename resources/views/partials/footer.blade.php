@php use App\Facades\Version; @endphp
<footer>
    &copy; {{ date('Y') }} {{ config('app.name', 'App') }} - Version: <a
        href="{{route('changelog')}}" target="_blank">{{ Version::getCurrentVersion() }}</a>
    - <a href="{{ route('impressum') }}" target="_blank">Impressum</a>
    - <a href="{{ route('datenschutz') }}" target="_blank">Datenschutz</a>
    - <a href="{{ route('tos') }}" target="_blank">Nutzungsbedingungen</a>
    - <a href="{{ route('license') }}" target="_blank">Lizenz</a>
    - <a href="{{ url(config('app.footer.roadmap')) }}" target="_blank">Roadmap</a>
    - <a href="{{ url(config('app.footer.issues')) }}" target="_blank">Bug gefunden?</a>
    <br/>
    <a href="https://github.com/N3XT0R/dashclip-delivery" target="_blank">❤️ GitHub</a>
    @if(false === app()->environment('production'))
        - <strong style="color: red;">Testinstanz - Nicht zur produktiven Verwendung</strong>
    @endif
</footer>
