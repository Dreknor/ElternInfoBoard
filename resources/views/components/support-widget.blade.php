{{--
    <x-support-widget />

    Globales Support-Widget mit FreeScout-Anbindung.
    Steuerung über Alpine.js, Request via Fetch API.
    Screenshot-Erstellung über html2canvas (CDN).

    Einbindung: einmalig im globalen Layout (z. B. layouts/app.blade.php)
    vor dem schließenden </body>-Tag.
--}}

@auth
<div
    x-data="supportWidget()"
    x-init="init()"
    x-cloak
    @support:open.window="open = true"
    class="support-widget-root"
    style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;"
>

    {{-- ═══ Trigger-Button (unten rechts) ════════════════════════════════ --}}
    {{-- Eigenes Recht: Sichtbarkeit des schwebenden Buttons wird unabhängig
         vom Button im Hilfe-Fenster gesteuert. --}}
    @can('use support widget')
    <button
        type="button"
        @click="open = true"
        title="Support kontaktieren"
        aria-label="Support-Widget öffnen"
        x-show="!open"
        class="fixed bottom-[calc(4.75rem+env(safe-area-inset-bottom))] right-4 lg:bottom-6 lg:right-6 z-[1050] flex items-center justify-center w-12 h-12 rounded-full shadow-lg
               bg-slate-700 hover:bg-slate-600 text-white transition-all duration-200 focus:outline-none
               focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
    >
        <i class="fas fa-headset text-lg"></i>
    </button>
    @endcan

    {{-- ═══ Backdrop ════════════════════════════════════════════════════ --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeModal()"
        class="fixed inset-0 z-[1049] bg-black/40 backdrop-blur-[2px]"
        style="display: none;"
    ></div>

    {{-- ═══ Modal ═══════════════════════════════════════════════════════ --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        @keydown.escape.window="closeModal()"
        class="fixed bottom-[calc(4.75rem+env(safe-area-inset-bottom))] right-4 left-4 sm:left-auto lg:bottom-6 lg:right-6 z-[1050] w-full max-w-sm rounded-2xl shadow-2xl border
               bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 overflow-hidden"
        style="display: none;"
        role="dialog"
        aria-modal="true"
        aria-label="Support-Anfrage senden"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-slate-700 text-white">
                    <i class="fas fa-headset text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100 m-0 leading-tight">Support kontaktieren</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 m-0 leading-tight">Wir antworten so schnell wie möglich.</p>
                </div>
            </div>
            <button
                type="button"
                @click="closeModal()"
                class="flex items-center justify-center w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600
                       hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors focus:outline-none"
                aria-label="Schließen"
            >
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-5 py-4">

            {{-- Erfolgsmeldung --}}
            <div x-show="success" class="flex flex-col items-center justify-center py-6 gap-3 text-center">
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-400">
                    <i class="fas fa-check text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 m-0">Anfrage übermittelt!</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 m-0 mt-1">Das Fenster schließt sich automatisch.</p>
                </div>
            </div>

            {{-- Fehlermeldung --}}
            <div x-show="errorMsg" class="mb-3 flex items-start gap-2 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700">
                <i class="fas fa-exclamation-circle text-red-500 dark:text-red-400 mt-0.5 flex-shrink-0"></i>
                <p class="text-xs text-red-700 dark:text-red-300 m-0" x-text="errorMsg"></p>
            </div>

            {{-- Formular --}}
            <div x-show="!success">
                <form @submit.prevent novalidate>

                    {{-- Nachricht --}}
                    <div class="mb-3">
                        <label
                            for="support-message"
                            class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1"
                        >Deine Nachricht <span class="text-red-500">*</span></label>
                        <textarea
                            id="support-message"
                            x-model="message"
                            rows="4"
                            maxlength="5000"
                            placeholder="Beschreibe dein Anliegen oder den aufgetretenen Fehler…"
                            :disabled="loading"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600
                                   bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100
                                   placeholder-slate-400 dark:placeholder-slate-500
                                   focus:outline-none focus:ring-2 focus:ring-slate-400 dark:focus:ring-slate-500
                                   disabled:opacity-50 resize-none transition"
                        ></textarea>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 text-right m-0 mt-0.5">
                            <span x-text="message.length"></span>/5000
                        </p>
                    </div>

                    {{-- Screenshot-Option --}}
                    <div class="mb-4">
                        <label class="flex items-start gap-3 cursor-pointer select-none group">
                            <div class="relative flex-shrink-0 mt-0.5">
                                <input
                                    type="checkbox"
                                    x-model="withScreenshot"
                                    :disabled="loading"
                                    class="sr-only peer"
                                >
                                <div
                                    class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-500
                                           peer-checked:bg-slate-700 peer-checked:border-slate-700
                                           dark:peer-checked:bg-slate-500 dark:peer-checked:border-slate-500
                                           peer-disabled:opacity-50 transition-colors flex items-center justify-center"
                                >
                                    <i x-show="withScreenshot" class="fas fa-check text-white" style="font-size: 8px;"></i>
                                </div>
                            </div>
                            <span class="text-xs text-slate-600 dark:text-slate-300 leading-snug">
                                Screenshot der aktuellen Seite anhängen
                                <span class="text-slate-400 dark:text-slate-500">(hilft bei der Fehleranalyse)</span>
                            </span>
                        </label>
                    </div>

                    {{-- Absenden-Button (type="button" verhindert Konflikte mit dem globalen jQuery-Submit-Handler) --}}
                    <button
                        type="button"
                        @click="submitTicket()"
                        :disabled="loading || !message.trim()"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium
                               text-white rounded-lg transition-all duration-200 focus:outline-none
                               focus:ring-2 focus:ring-slate-400 focus:ring-offset-1
                               bg-slate-700 hover:bg-slate-600
                               disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{-- Lade-Spinner --}}
                        <svg
                            x-show="loading"
                            class="animate-spin w-4 h-4"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>

                        <span x-text="loading ? 'Wird gesendet…' : 'Anfrage absenden'"></span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900">
            <p class="text-[10px] text-slate-400 dark:text-slate-500 text-center m-0">
                Deine Anfrage wird als Support-Ticket in unserem Helpdesk erfasst.
            </p>
        </div>
    </div>

</div>

{{-- html2canvas – für die Screenshot-Funktion --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"
        integrity="sha512-BNaRQnYJYiPSqHHDb58B0yaPfCu+Wgds8Gp/gU33kqBtgNS4tSPHuGibyoeqMV/TJlSKda6FXzoEyYGjTe+vXA=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
        defer></script>

<script>
/**
 * Alpine.js-Komponente: supportWidget
 * Verwaltet Sichtbarkeit, Screenshot-Erfassung und API-Kommunikation.
 */
function supportWidget() {
    return {
        /** Modal offen/geschlossen */
        open: false,
        /** Nachrichtentext */
        message: '',
        /** Screenshot-Checkbox */
        withScreenshot: false,
        /** Ladeindikator */
        loading: false,
        /** Erfolgszustand */
        success: false,
        /** Fehlermeldung */
        errorMsg: '',

        /** Lifecycle-Hook: CSRF-Token sicherstellen */
        init() {
            // nichts nötig – CSRF via Meta-Tag
        },

        /** Schließt das Modal und setzt den Zustand zurück */
        closeModal() {
            if (this.loading) return;
            this.open        = false;
            this.message     = '';
            this.withScreenshot = false;
            this.success     = false;
            this.errorMsg    = '';
        },

        /** Sendet das Ticket an den Laravel-Controller */
        async submitTicket() {
            if (!this.message.trim()) return;

            this.loading  = true;
            this.errorMsg = '';

            let screenshot = null;

            // Screenshot erstellen, wenn gewünscht
            if (this.withScreenshot && typeof html2canvas === 'function') {
                try {
                    // Widget vorübergehend ausblenden, damit es nicht im Screenshot erscheint
                    const widgetEl = this.$el;
                    widgetEl.style.visibility = 'hidden';

                    const canvas = await html2canvas(document.body, {
                        useCORS: true,
                        logging: false,
                        scale: Math.min(window.devicePixelRatio, 2),
                    });

                    widgetEl.style.visibility = '';
                    screenshot = canvas.toDataURL('image/png');
                } catch (screenshotErr) {
                    console.warn('[SupportWidget] Screenshot fehlgeschlagen:', screenshotErr);
                    // Kein harter Abbruch – Ticket ohne Screenshot senden
                }
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            try {
                const response = await fetch('{{ route('support.ticket') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  csrfToken,
                    },
                    body: JSON.stringify({
                        message:    this.message,
                        screenshot: screenshot,
                        page_url:   window.location.href,
                    }),
                });

                // Antwort-Body auslesen – auch wenn kein gültiges JSON zurückkommt
                let data = {};
                try {
                    data = await response.json();
                } catch (_) {
                    data = { success: false, message: 'Ungültige Server-Antwort (kein JSON).' };
                }

                if (!response.ok || !data.success) {
                    // Validierungsfehler (422) aufbereiten
                    if (response.status === 422 && data.errors) {
                        const firstError = Object.values(data.errors).flat()[0];
                        this.errorMsg = firstError ?? 'Ungültige Eingabe.';
                    } else {
                        this.errorMsg = data.message ?? 'Ein Fehler ist aufgetreten. Bitte versuche es erneut.';
                    }
                    return;
                }

                // Erfolgszustand – Modal schließt sich nach 4 Sekunden automatisch
                this.success = true;
                setTimeout(() => this.closeModal(), 4000);

            } catch (networkErr) {
                console.error('[SupportWidget] Netzwerkfehler:', networkErr);
                this.errorMsg = 'Verbindungsfehler. Bitte überprüfe deine Internetverbindung.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endauth


