@if (!request()->hasCookie('cookie_consent'))
    <div
            id="cookie-banner"
            style="
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 99999;
            background: #111827; /* dunkles Grau */
            color: white;
            padding: 14px 20px;
            font-size: 14px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        "
    >
        <span style="max-width: 900px; line-height: 1.4;">
            Diese Website verwendet ausschließlich technisch notwendige Cookies.
            Weitere Informationen findest du in unserer
            <a href="{{ route('datenschutz') }}"
               style="color:#60a5fa; text-decoration: underline;"
               target="_blank">
               Datenschutzerklärung
            </a>.
        </span>

        <button
                id="cookie-accept"
                style="
                background: #374151;
                color: white;
                padding: 6px 14px;
                border-radius: 6px;
                border: none;
                cursor: pointer;
            "
        >
            OK
        </button>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("cookie-accept").addEventListener("click", function () {
                const d = new Date();
                d.setFullYear(d.getFullYear() + 1);
                document.cookie = "cookie_consent=true; expires=" + d.toUTCString() + "; path=/; SameSite=Lax";
                document.getElementById("cookie-banner").remove();
            });
        });
    </script>
@endif
