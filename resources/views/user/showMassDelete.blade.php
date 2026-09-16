@extends('layouts.app')

@section('title')
    - Elternkonten löschen
@endsection

@section('content')
    <div class="container-fluid px-4 py-6">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <p class="text-uppercase text-warning mb-1 font-weight-bold" style="letter-spacing: .08em; font-size: 12px;">
                    Sicherheitsfunktion
                </p>
                <h1 class="h3 mb-0">Elternkonten löschen</h1>
            </div>

            <a href="{{ url('users') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i>
                Zurück zur Übersicht
            </a>
        </div>

        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <div class="d-flex align-items-start gap-3">
                <div class="flex-shrink-0 mt-1">
                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                </div>
                <div>
                    <strong>Bitte vor dem Löschen beachten:</strong>
                    <p class="mb-0 mt-2">
                        Beim Entfernen eines Benutzerkontos wird die Person aus aktiven Familien- und
                        Pflichtstunden-Berechnungen herausgenommen. Bereits erfasste Pflichtstunden bleiben in der
                        Historie erhalten, zählen in der laufenden Periode aber nicht mehr mit.
                    </p>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Gesamt</div>
                            <div class="h3 mb-0">{{ $users->count() }}</div>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Ausgewählt</div>
                            <div class="h3 mb-0"><span id="selectedCount">0</span></div>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-check-square"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Sicher</div>
                            <div class="h3 mb-0">Ja</div>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h5 class="mb-0">Auswahl bestätigen</h5>
                        <small class="text-muted">Nur Elternkonten werden aufgelistet.</small>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" id="selectAllUsers" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-check-double"></i>
                            Alle auswählen
                        </button>
                        <button type="button" id="clearSelection" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i>
                            Auswahl löschen
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="mb-3">
                    <label for="massDeleteSearch" class="small text-uppercase text-muted mb-2 d-block">Suche</label>
                    <input id="massDeleteSearch" type="search" class="form-control" placeholder="Nach Name oder E-Mail suchen…">
                </div>

                <form action="{{ url('users/mass/delete') }}" method="post" id="massDeleteForm">
                    @csrf
                    @method('delete')

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="userTable">
                            <thead>
                            <tr>
                                <th class="w-12">
                                    <input type="checkbox" id="selectAllCheckbox" aria-label="Alle Benutzer auswählen">
                                </th>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Gruppen</th>
                                <th>Rechte</th>
                                <th>Verknüpft</th>
                                <th>letzte E-Mail</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($users as $user)
                                <tr data-search="{{ strtolower($user->name.' '.$user->email) }}">
                                    <td>
                                        <input type="checkbox"
                                              name="user_ids[]"
                                              value="{{ $user->id }}"
                                              class="user-checkbox"
                                              data-user-name="{{ $user->name }}"
                                              aria-label="{{ $user->name }} auswählen">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                           <div class="rounded-circle bg-light text-dark d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: .85rem;">
                                               {{ strtoupper(mb_substr($user->name, 0, 1, 'UTF-8')) }}
                                           </div>
                                           <div>
                                               <div class="fw-semibold">{{ $user->name }}</div>
                                               <div class="small text-muted">
                                                   {{ $user->roles->pluck('name')->join(', ') ?: 'Keine Rolle' }}
                                               </div>
                                           </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $user->email }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                           @forelse($user->groups as $gruppe)
                                               <span class="badge badge-info">{{ $gruppe->name }}</span>
                                           @empty
                                               <span class="text-muted small">Keine Gruppen</span>
                                           @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                           @foreach($user->roles as $role)
                                               <span class="badge badge-warning">{{ $role->name }}</span>
                                           @endforeach
                                           @foreach($user->permissions as $permission)
                                               <span class="badge badge-danger">{{ $permission->name }}</span>
                                           @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        @if($user->sorgeberechtigter2)
                                           <span class="badge badge-secondary">{{ $user->sorgeberechtigter2->name }}</span>
                                        @else
                                           <span class="text-muted small">Keine Verknüpfung</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->lastEmail)
                                           <span class="badge {{ $user->lastEmail->lessThan(now()->subDays(7)) ? 'badge-danger' : 'badge-success' }}">
                                               {{ $user->lastEmail->format('d.m.Y') }}
                                           </span>
                                        @else
                                           <span class="text-muted small">Nie</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <small class="text-muted">
                            Die Auswahl wird beim Absenden per Sicherheitsbestätigung geprüft.
                        </small>
                        <button type="submit" id="deleteSelectedBtn" class="btn btn-danger" disabled>
                            <i class="fas fa-trash-alt"></i>
                            Ausgewählte Konten löschen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('massDeleteSearch');
            const tableBody = document.querySelector('#userTable tbody');
            const rows = () => Array.from(tableBody.querySelectorAll('tr'));
            const checkboxes = () => Array.from(document.querySelectorAll('.user-checkbox'));
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const selectedCountEl = document.getElementById('selectedCount');
            const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
            const selectAllBtn = document.getElementById('selectAllUsers');
            const clearSelectionBtn = document.getElementById('clearSelection');

            function updateSelectionState() {
                const items = checkboxes();
                const selected = items.filter((checkbox) => checkbox.checked).length;
                const allSelected = items.length > 0 && items.every((checkbox) => checkbox.checked);

                selectedCountEl.textContent = selected;
                deleteSelectedBtn.disabled = selected === 0;
                selectAllCheckbox.checked = allSelected;
            }

            searchInput.addEventListener('input', function () {
                const term = this.value.trim().toLowerCase();

                rows().forEach((row) => {
                    const haystack = (row.dataset.search || row.textContent || '').toLowerCase();
                    row.style.display = haystack.includes(term) ? '' : 'none';
                });
            });

            selectAllCheckbox.addEventListener('change', function () {
                checkboxes().forEach((checkbox) => {
                    checkbox.checked = this.checked;
                });
                updateSelectionState();
            });

            selectAllBtn.addEventListener('click', function () {
                checkboxes().forEach((checkbox) => {
                    checkbox.checked = true;
                });
                updateSelectionState();
            });

            clearSelectionBtn.addEventListener('click', function () {
                checkboxes().forEach((checkbox) => {
                    checkbox.checked = false;
                });
                updateSelectionState();
            });

            checkboxes().forEach((checkbox) => {
                checkbox.addEventListener('change', updateSelectionState);
            });

            document.getElementById('massDeleteForm').addEventListener('submit', function (event) {
                const selected = checkboxes().filter((checkbox) => checkbox.checked);
                if (!selected.length) {
                    event.preventDefault();
                    return;
                }

                const names = selected.slice(0, 3).map((checkbox) => checkbox.dataset.userName || 'Benutzer');
                const suffix = selected.length > 3 ? ` und ${selected.length - 3} weitere` : '';
                const confirmationMessage = `Die ausgewählten Konten (${selected.length}) wirklich löschen?\n${names.join(', ')}${suffix}\n\nAchtung: Pflichtstunden bleiben in der Historie erhalten, fallen aber aus der laufenden Berechnung heraus.`;

                if (!window.confirm(confirmationMessage)) {
                    event.preventDefault();
                }
            });

            updateSelectionState();
        });
    </script>
@endsection

