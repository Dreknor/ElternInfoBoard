@extends('layouts.app')

@section('title')
    - Krankmeldung Express
@endsection

@section('content')
    <div class="rounded-lg shadow-lg overflow-hidden mb-6" style="background-color: var(--color-card-bg)">
        <!-- Card Header -->
        <div class="px-4 py-3 border-b"
             style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border)">
            <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text)">
                <i class="fas fa-bolt"></i>
                Krankmeldung Express
            </h5>
            <p class="text-sm mb-0 mt-1" style="color: var(--color-widget-header-text)">
                Tastaturoptimierte Schnellerfassung für telefonische Krankmeldungen.
            </p>
        </div>

        <!-- Card Body -->
        <div class="p-4 md:p-6">
            <!-- Schnellsuche -->
            <div id="expressSearchWrapper" class="relative mb-4">
                <label for="expressSearchInput" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary)">
                    <i class="fas fa-search mr-1" style="color: var(--color-text-secondary)"></i>
                    Kind suchen (Vorname, Nachname oder Klasse/Gruppe)
                </label>
                <input type="text"
                       id="expressSearchInput"
                       autocomplete="off"
                       placeholder="z. B. max 3b"
                       class="w-full px-4 py-3 border-2 rounded-lg transition-all duration-200 outline-none text-lg"
                       style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)">

                <div id="expressSearchResults"
                     class="fixed z-50 rounded-lg border-2 shadow-lg hidden max-h-72 overflow-y-auto"
                     style="border-color: var(--color-input-border); background-color: var(--color-card-bg)"></div>
            </div>

            <!-- Erfassungsmaske -->
            <div id="expressForm" class="hidden rounded-lg border-2 p-4" style="border-color: var(--color-widget-primary-border); background-color: var(--color-surface-subtle)">
                <div class="flex items-center justify-between mb-4">
                    <h6 class="font-bold text-lg mb-0 flex items-center gap-2" style="color: var(--color-text-primary)">
                        <i class="fas fa-child" style="color: var(--color-widget-primary-border)"></i>
                        <span id="expressSelectedName">-</span>
                    </h6>
                    <button type="button" id="expressCancelBtn"
                            class="text-sm px-3 py-1 rounded-lg" style="color: var(--color-text-secondary)">
                        <i class="fas fa-times mr-1"></i>Abbrechen
                    </button>
                </div>

                <div id="expressAlreadySick" class="hidden flex items-start gap-2 p-3 mb-4 border-l-4 rounded-lg"
                     style="background-color: var(--color-widget-body-bg); border-color: var(--color-widget-warning-from)">
                    <i class="fas fa-exclamation-triangle mt-0.5" style="color: var(--color-widget-warning-from)"></i>
                    <p class="text-sm mb-0" style="color: var(--color-widget-warning-border)">
                        Dieses Kind ist für heute bereits krankgemeldet. Eine erneute Meldung wird ggf. abgelehnt.
                    </p>
                </div>

                <!-- Presets -->
                <div class="flex flex-wrap gap-2 mb-4" role="group" aria-label="Zeitraum-Schnellauswahl">
                    <button type="button" data-preset="today" class="express-preset-btn px-4 py-2 rounded-lg text-sm font-medium border-2"
                            style="border-color: var(--color-widget-primary-border); background-color: var(--color-widget-primary-border); color: #fff">
                        Heute
                    </button>
                    <button type="button" data-preset="today_tomorrow" class="express-preset-btn px-4 py-2 rounded-lg text-sm font-medium border-2"
                            style="border-color: var(--color-input-border); color: var(--color-text-primary)">
                        Heute &amp; Morgen
                    </button>
                    <button type="button" data-preset="rest_of_week" class="express-preset-btn px-4 py-2 rounded-lg text-sm font-medium border-2"
                            style="border-color: var(--color-input-border); color: var(--color-text-primary)">
                        Restliche Woche
                    </button>
                    <button type="button" data-preset="custom" class="express-preset-btn px-4 py-2 rounded-lg text-sm font-medium border-2"
                            style="border-color: var(--color-input-border); color: var(--color-text-primary)">
                        Benutzerdefiniert
                    </button>
                </div>

                <!-- Datumsauswahl (nur sichtbar bei "Benutzerdefiniert") -->
                <div id="expressCustomDates" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="expressStart" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary)">von</label>
                        <input type="date" id="expressStart"
                               class="w-full px-4 py-2 border-2 rounded-lg outline-none"
                               style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)">
                    </div>
                    <div>
                        <label for="expressEnde" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary)">bis</label>
                        <input type="date" id="expressEnde"
                               class="w-full px-4 py-2 border-2 rounded-lg outline-none"
                               style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)">
                    </div>
                </div>

                <p class="text-sm mb-4" style="color: var(--color-text-secondary)">
                    Zeitraum: <strong id="expressRangeLabel">heute</strong>
                </p>

                <!-- Bemerkung -->
                <div class="mb-4">
                    <label for="expressKommentar" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary)">
                        Bemerkung (optional)
                    </label>
                    <input type="text" id="expressKommentar" maxlength="1000" placeholder='z. B. "Fieber", "Attest folgt"'
                           class="w-full px-4 py-2 border-2 rounded-lg outline-none"
                           style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)">
                </div>

                <button type="button" id="expressSubmitBtn"
                        class="inline-flex items-center justify-center gap-2 w-full md:w-auto px-6 py-2.5 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg"
                        style="background-color: var(--color-widget-success-from)">
                    <i class="fas fa-check-circle"></i>
                    <span>Krankmelden</span>
                    <kbd class="text-xs px-1.5 py-0.5 rounded bg-black/20">Enter</kbd>
                </button>
            </div>
        </div>
    </div>

    <!-- Aktuelle Krankmeldungen -->
    <div class="rounded-lg shadow-lg overflow-hidden" style="background-color: var(--color-card-bg)">
        <!-- Card Header -->
        <div class="px-4 py-3 border-b"
             style="background: linear-gradient(to right, var(--color-widget-success-from), var(--color-widget-success-to)); border-color: var(--color-widget-success-border)">
            <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text)">
                <i class="fas fa-notes-medical"></i>
                Aktuell krankgemeldete Kinder
            </h5>
        </div>

        <!-- Card Body -->
        <div id="expressCurrentList">
            @include('krankmeldung.partials.express-current-list')
        </div>
    </div>

    <!-- Toast -->
    <div id="expressToast" class="fixed bottom-6 right-6 z-50 hidden max-w-sm px-4 py-3 rounded-lg shadow-lg text-white"
         style="background-color: var(--color-widget-success-from)"></div>
@endsection

@push('js')
<script>
(function () {
    const searchInput   = document.getElementById('expressSearchInput');
    const resultsDiv     = document.getElementById('expressSearchResults');
    const formPanel      = document.getElementById('expressForm');
    const selectedNameEl = document.getElementById('expressSelectedName');
    const alreadySickEl  = document.getElementById('expressAlreadySick');
    const customDatesEl  = document.getElementById('expressCustomDates');
    const startInput     = document.getElementById('expressStart');
    const endeInput      = document.getElementById('expressEnde');
    const rangeLabel      = document.getElementById('expressRangeLabel');
    const kommentarInput = document.getElementById('expressKommentar');
    const submitBtn       = document.getElementById('expressSubmitBtn');
    const cancelBtn       = document.getElementById('expressCancelBtn');
    const toast            = document.getElementById('expressToast');
    const presetButtons   = document.querySelectorAll('.express-preset-btn');

    const searchUrl = '{{ route('krankmeldung.express.search') }}';
    const storeUrl  = '{{ route('krankmeldung.express.store') }}';
    const currentListUrl = '{{ route('krankmeldung.express.current') }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let currentResults = [];
    let highlightedIndex = -1;
    let selectedChild = null;
    let debounceTimer = null;

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function toIso(date) { return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()); }
    function fmt(iso) {
        const [y, m, d] = iso.split('-');
        return d + '.' + m + '.' + y;
    }

    function activePresetStyle(btn, active) {
        if (active) {
            btn.style.backgroundColor = 'var(--color-widget-primary-border)';
            btn.style.color = '#fff';
        } else {
            btn.style.backgroundColor = '';
            btn.style.color = 'var(--color-text-primary)';
        }
    }

    function setRange(startIso, endeIso) {
        startInput.value = startIso;
        endeInput.value = endeIso;
        rangeLabel.textContent = startIso === endeIso ? ('heute (' + fmt(startIso) + ')') : (fmt(startIso) + ' bis ' + fmt(endeIso));
    }

    function applyPreset(preset) {
        const today = new Date();

        presetButtons.forEach((btn) => activePresetStyle(btn, btn.dataset.preset === preset));

        if (preset === 'custom') {
            customDatesEl.classList.remove('hidden');
            return;
        }

        customDatesEl.classList.add('hidden');

        if (preset === 'today') {
            const iso = toIso(today);
            setRange(iso, iso);
        } else if (preset === 'today_tomorrow') {
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            setRange(toIso(today), toIso(tomorrow));
        } else if (preset === 'rest_of_week') {
            const friday = new Date(today);
            const day = friday.getDay(); // 0 = Sonntag, 5 = Freitag
            const diffToFriday = day <= 5 ? (5 - day) : 0;
            friday.setDate(friday.getDate() + diffToFriday);
            setRange(toIso(today), toIso(friday));
        }
    }

    presetButtons.forEach((btn) => {
        btn.addEventListener('click', () => applyPreset(btn.dataset.preset));
    });

    [startInput, endeInput].forEach((input) => {
        input.addEventListener('change', () => {
            rangeLabel.textContent = startInput.value === endeInput.value
                ? ('heute (' + fmt(startInput.value) + ')')
                : (fmt(startInput.value) + ' bis ' + fmt(endeInput.value));
        });
    });

    function positionResults() {
        const rect = searchInput.getBoundingClientRect();
        resultsDiv.style.left = rect.left + 'px';
        resultsDiv.style.top = (rect.bottom + 4) + 'px';
        resultsDiv.style.width = rect.width + 'px';
    }

    function renderResults() {
        if (!currentResults.length) {
            resultsDiv.innerHTML = '<p class="p-3 text-sm" style="color: var(--color-text-muted)">Keine Treffer gefunden</p>';
            positionResults();
            resultsDiv.classList.remove('hidden');
            return;
        }

        resultsDiv.innerHTML = currentResults.map((child, index) => {
            const label = [child.group, child.class].filter(Boolean).join(' / ');
            const activeStyle = index === highlightedIndex ? 'background-color: var(--color-surface-subtle);' : '';
            const sickBadge = child.sick_today
                ? '<span class="ml-2 text-xs px-2 py-0.5 rounded-full" style="background-color: var(--color-widget-warning-from); color:#fff"><i class="fas fa-exclamation-triangle mr-1"></i>bereits krank</span>'
                : '';

            return `<button type="button" data-index="${index}"
                        class="express-result-item w-full text-left px-4 py-2.5 flex items-center justify-between border-b last:border-0"
                        style="${activeStyle} border-color: var(--color-card-border)">
                        <span>
                            <span class="font-medium" style="color: var(--color-text-primary)">${child.first_name} ${child.last_name}</span>
                            <span class="text-sm ml-2" style="color: var(--color-text-secondary)">${label}</span>
                        </span>
                        ${sickBadge}
                    </button>`;
        }).join('');

        positionResults();
        resultsDiv.classList.remove('hidden');

        resultsDiv.querySelectorAll('.express-result-item').forEach((btn) => {
            btn.addEventListener('click', () => selectChild(parseInt(btn.dataset.index, 10)));
        });
    }

    window.addEventListener('resize', () => {
        if (!resultsDiv.classList.contains('hidden')) positionResults();
    });
    window.addEventListener('scroll', () => {
        if (!resultsDiv.classList.contains('hidden')) positionResults();
    }, true);


    function selectChild(index) {
        const child = currentResults[index];
        if (!child) return;

        selectedChild = child;
        selectedNameEl.textContent = child.first_name + ' ' + child.last_name +
            ([child.group, child.class].filter(Boolean).length ? ' (' + [child.group, child.class].filter(Boolean).join(' / ') + ')' : '');
        alreadySickEl.classList.toggle('hidden', !child.sick_today);

        resultsDiv.classList.add('hidden');
        formPanel.classList.remove('hidden');

        kommentarInput.value = '';
        applyPreset('today');

        kommentarInput.focus();
    }

    function resetToSearch() {
        selectedChild = null;
        currentResults = [];
        highlightedIndex = -1;
        formPanel.classList.add('hidden');
        resultsDiv.classList.add('hidden');
        searchInput.value = '';
        searchInput.focus();
    }

    cancelBtn.addEventListener('click', resetToSearch);

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        highlightedIndex = -1;

        if (q.length < 2) {
            resultsDiv.classList.add('hidden');
            currentResults = [];
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then((r) => r.json())
                .then((data) => {
                    currentResults = data;
                    highlightedIndex = data.length ? 0 : -1;
                    renderResults();
                })
                .catch(() => {
                    resultsDiv.innerHTML = '<p class="p-3 text-sm text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>Suche fehlgeschlagen</p>';
                    positionResults();
                    resultsDiv.classList.remove('hidden');
                });
        }, 180);
    });

    searchInput.addEventListener('keydown', function (e) {
        if (resultsDiv.classList.contains('hidden') || !currentResults.length) {
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlightedIndex = Math.min(highlightedIndex + 1, currentResults.length - 1);
            renderResults();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlightedIndex = Math.max(highlightedIndex - 1, 0);
            renderResults();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlightedIndex >= 0) {
                selectChild(highlightedIndex);
            }
        } else if (e.key === 'Escape') {
            resultsDiv.classList.add('hidden');
        }
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('expressSearchWrapper').contains(e.target)) {
            resultsDiv.classList.add('hidden');
        }
    });

    function showToast(message, isError) {
        toast.textContent = message;
        toast.style.backgroundColor = isError ? 'var(--color-widget-danger-from, #dc2626)' : 'var(--color-widget-success-from)';
        toast.classList.remove('hidden');
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    function submitKrankmeldung() {
        if (!selectedChild) return;

        submitBtn.disabled = true;

        fetch(storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                child_id: selectedChild.id,
                start: startInput.value,
                ende: endeInput.value,
                kommentar: kommentarInput.value,
            }),
        })
            .then(async (r) => {
                const data = await r.json();
                if (!r.ok) {
                    throw new Error(data.message || 'Fehler beim Speichern.');
                }
                return data;
            })
            .then((data) => {
                showToast(data.message, false);
                resetToSearch();
                refreshCurrentList();
            })
            .catch((err) => {
                showToast(err.message, true);
            })
            .finally(() => {
                submitBtn.disabled = false;
            });
    }

    function refreshCurrentList() {
        fetch(currentListUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => r.text())
            .then((html) => {
                document.getElementById('expressCurrentList').innerHTML = html;
            })
            .catch(() => {});
    }

    submitBtn.addEventListener('click', submitKrankmeldung);

    kommentarInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            submitKrankmeldung();
        }
    });

    formPanel.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            submitKrankmeldung();
        }
    });

    searchInput.focus();
})();
</script>
@endpush
