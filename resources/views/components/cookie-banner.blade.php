@if (!request()->hasCookie('cookie_consent'))
    <div
            id="cookie-banner"
            class="fixed bottom-0 inset-x-0 z-[9999] bg-gray-900 text-white p-4 shadow-lg"
    >
        <div class="max-w-4xl mx-auto flex flex-col md:flex-row items-center justify-between gap-3">
            <span class="text-sm leading-snug">
                Diese Website verwendet ausschließlich technisch notwendige Cookies.
                Weitere Informationen findest du in unserer
                <a href="{{route('datenschutz')}}" class="underline text-gray-300 hover:text-white" target="_blank">
                    Datenschutzerklärung
                </a>.
            </span>

            <button
                    id="cookie-accept"
                    class="bg-primary-600 hover:bg-primary-700 text-white text-sm px-4 py-2 rounded"
            >
                OK
            </button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const banner = document.getElementById("cookie-banner");

            document.getElementById("cookie-accept").addEventListener("click", function () {
                let d = new Date();
                d.setFullYear(d.getFullYear() + 1);

                document.cookie = "cookie_consent=true; expires=" + d.toUTCString() + "; path=/; SameSite=Lax";

                banner.remove();
            });
        });
    </script>
@endif
