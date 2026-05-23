{{-- Formular zum Starten einer Direktnachricht mit einem Nutzer aus der eigenen Gruppe --}}
<div id="userSearchForm" class="mb-4">
    <div class="relative">
        <input type="text"
               id="userSearchInput"
               placeholder="Name eingeben..."
               autocomplete="off"
               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg outline-none text-sm"
               style="transition: border-color 0.2s"
               onfocus="this.style.borderColor='var(--color-primary)'; this.style.boxShadow='0 0 0 2px var(--color-primary-light)'"
               onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow=''">
        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
    </div>
    <div id="userSearchResults" class="mt-2 rounded-lg border border-gray-200 hidden max-h-48 overflow-y-auto"></div>
</div>

<p class="text-xs text-gray-400 flex items-center gap-1.5">
    <i class="fas fa-info-circle" style="color: var(--color-primary)"></i>
    Du kannst nur Mitglieder aus deinen gemeinsamen Gruppen anschreiben.
</p>

@push('js')
<script>
const searchInput = document.getElementById('userSearchInput');
const resultsDiv  = document.getElementById('userSearchResults');

// CSS-Variablen aus dem Theme lesen
function getPrimaryColor() {
    return getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#3b82f6';
}
function getPrimaryBg() {
    return getComputedStyle(document.documentElement).getPropertyValue('--color-widget-primary-bg').trim() || '#eff6ff';
}

if (searchInput) {
    let timeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const q = this.value.trim();
        if (q.length < 2) { resultsDiv.classList.add('hidden'); return; }
        timeout = setTimeout(() => {
            fetch('{{ route('messenger.users.search') }}?q=' + encodeURIComponent(q), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(r => r.json())
            .then(data => {
                const primaryBg = getPrimaryBg();
                const primary = getPrimaryColor();
                if (!data.length) {
                    resultsDiv.innerHTML = '<p class="p-3 text-sm text-gray-400">Keine Treffer gefunden</p>';
                } else {
                    resultsDiv.innerHTML = data.map(u =>
                        `<button type="button"
                            onclick="startDirect(${u.id})"
                            class="w-full text-left px-4 py-2.5 flex items-center gap-3 transition-colors border-b border-gray-100 last:border-0"
                            onmouseover="this.style.backgroundColor='${primaryBg}'"
                            onmouseout="this.style.backgroundColor=''">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                 style="background-color: ${primary}">
                                ${u.name.charAt(0).toUpperCase()}
                            </div>
                            <span class="text-sm font-medium text-gray-800">${u.name}</span>
                        </button>`
                    ).join('');
                }
                resultsDiv.classList.remove('hidden');
            })
            .catch(() => {
                resultsDiv.innerHTML = '<p class="p-3 text-sm text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>Suche fehlgeschlagen</p>';
                resultsDiv.classList.remove('hidden');
            });
        }, 300);
    });

    // Klick außerhalb schließt die Ergebnisliste
    document.addEventListener('click', function(e) {
        if (!document.getElementById('userSearchForm').contains(e.target)) {
            resultsDiv.classList.add('hidden');
        }
    });
}

function startDirect(userId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/messenger/direct/' + userId;
    const csrf = document.createElement('input');
    csrf.type  = 'hidden';
    csrf.name  = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush
