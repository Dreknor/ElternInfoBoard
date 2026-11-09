@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>Kinder</h3>
                <p>
                    Übersicht aller Kinder
                </p>
                @can('edit schickzeiten')
                    <p>
                        <a href="{{ route('child.create') }}" class="btn btn-primary">Neues Kind anlegen</a>
                    </p>
                @endcan


                <div class="form-row mt-3">
                    <div class="col-md-4 mb-2">
                        <label for="search" class="sr-only">Suche</label>
                        <input type="text" class="form-control" id="search" placeholder="Nach Namen suchen...">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="groupFilter" class="sr-only">Gruppe filtern</label>
                        <select class="custom-select" id="groupFilter">
                            <option value="">Alle Gruppen</option>
                            @foreach($children->pluck('group')->filter()->unique('id')->sortBy('name') as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="classFilter" class="sr-only">Klasse filtern</label>
                        <select class="custom-select" id="classFilter">
                            <option value="">Alle Klassen</option>
                            @foreach($children->pluck('class')->filter()->unique('id')->sortBy('name') as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="statusFilter" class="sr-only">Status filtern</label>
                        <select class="custom-select" id="statusFilter">
                            <option value="">Alle Status</option>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-center">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="duplicatesOnly">
                            <label class="custom-control-label" for="duplicatesOnly">Nur doppelte</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered" id="childrenTable">
                    <thead>
                    <tr>
                        <th>Vorname</th>
                        <th>Nachname</th>
                        <th>Gruppe / Klasse</th>
                        <th>Eltern</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($children as $child)
                        <tr data-group-id="{{ $child->group_id ?? '' }}"
                            data-class-id="{{ $child->class_id ?? '' }}"
                            data-status="{{ $child->status }}"
                            data-duplicate="{{ in_array($child->id, $duplicateIds, true) ? '1' : '0' }}">
                            <td>{{ $child->first_name }}</td>
                            <td>{{ $child->last_name }}</td>
                            <td>
                                @if($child->group)
                                    <span class="badge badge-success p-2">{{$child->group->name}}</span>
                                @else
                                    <span class="badge badge-danger p-2">Keine Gruppe zugeordnet</span>
                                @endif

                                @if($child->class)
                                    <span class="badge badge-info p-2">{{$child->class->name}}</span>
                                @else
                                    <span class="badge badge-warning p-2">Keine Klasse zugeordnet</span>
                                @endif
                            </td>
                            <td>
                                @foreach($child->parents as $parent)
                                    <div>
                                        {{ $parent->name }}
                                        <small class="text-muted">({{ $parent->pivot->relationType()->label() }}@unless($parent->pivot->has_custody), ohne Sorgerecht @endunless)</small>
                                        @if($parent->pivot->isPendingReview())
                                            <span class="badge badge-warning">ungeprüft</span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                            @php
                                $custodianContacts = $child->parents
                                    ->filter(function ($guardian) {
                                        $validUntil = $guardian->pivot->valid_until;

                                        return $guardian->pivot->has_custody
                                            && ($validUntil === null || $validUntil->isToday() || $validUntil->isFuture());
                                    })
                                    ->map(fn ($guardian) => [
                                        'name' => $guardian->name,
                                        'relation' => $guardian->pivot->relationType()->label(),
                                        'email' => $guardian->email,
                                        'publicMail' => $guardian->publicMail,
                                        'phone' => $guardian->phone,
                                        'publicPhone' => $guardian->publicPhone,
                                    ])
                                    ->values();
                            @endphp
                            <td>
                                <button type="button" class="btn btn-secondary btn-sm mb-1 mb-md-0 js-show-contacts"
                                        data-child="{{ $child->first_name }} {{ $child->last_name }}"
                                        data-contacts='@json($custodianContacts)'
                                        title="Kontaktdaten der Sorgeberechtigten">
                                    <i class="fas fa-address-book"></i> Kontakte
                                </button>
                                @can('edit schickzeiten')
                                    <a href="{{ route('child.edit', $child->id) }}" class="btn btn-primary btn-sm mb-1 mb-md-0">Edit</a>
                                    <a href="{{ route('child.mandates.edit', $child->id) }}" class="btn btn-info btn-sm mb-1 mb-md-0">Vollmachten</a>

                                    <form action="{{ route('child.destroy', $child->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                @endcan
                            </td>

                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="contactsModal" tabindex="-1" role="dialog" aria-labelledby="contactsModalTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactsModalTitle">Kontaktdaten der Sorgeberechtigten</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Schließen">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="contactsModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            function escapeHtml(value) {
                var div = document.createElement('div');
                div.textContent = value == null ? '' : String(value);
                return div.innerHTML;
            }

            function contactLine(icon, label, value, href) {
                if (!value) {
                    return '';
                }
                return '<div><i class="fas ' + icon + ' fa-fw text-muted"></i> '
                    + '<small class="text-muted">' + escapeHtml(label) + ':</small> '
                    + '<a href="' + escapeHtml(href) + '">' + escapeHtml(value) + '</a></div>';
            }

            document.querySelectorAll('.js-show-contacts').forEach(function (button) {
                button.addEventListener('click', function () {
                    var contacts = JSON.parse(button.dataset.contacts || '[]');
                    var html = '';

                    if (contacts.length === 0) {
                        html = '<p class="text-muted mb-0">Keine aktuell Sorgeberechtigten hinterlegt.</p>';
                    } else {
                        contacts.forEach(function (c, index) {
                            html += '<div class="' + (index > 0 ? 'border-top pt-2 mt-2' : '') + '">'
                                + '<strong>' + escapeHtml(c.name) + '</strong> '
                                + '<small class="text-muted">(' + escapeHtml(c.relation) + ')</small>'
                                + contactLine('fa-envelope', 'E-Mail', c.email, 'mailto:' + c.email)
                                + (c.publicMail && c.publicMail !== c.email ? contactLine('fa-envelope-open', 'Öffentliche E-Mail', c.publicMail, 'mailto:' + c.publicMail) : '')
                                + contactLine('fa-phone', 'Telefon (nichtöffentlich)', c.phone, 'tel:' + c.phone)
                                + (c.publicPhone && c.publicPhone !== c.phone ? contactLine('fa-phone-alt', 'Öffentliches Telefon', c.publicPhone, 'tel:' + c.publicPhone) : '')
                                + (!c.email && !c.phone && !c.publicPhone && !c.publicMail ? '<div class="text-muted small">Keine Kontaktdaten hinterlegt.</div>' : '')
                                + '</div>';
                        });
                    }

                    document.getElementById('contactsModalTitle').textContent = 'Sorgeberechtigte von ' + button.dataset.child;
                    document.getElementById('contactsModalBody').innerHTML = html;
                    $('#contactsModal').modal('show');
                });
            });
        })();

        function filterChildren() {
            var value = document.getElementById('search').value.toLowerCase().trim();
            var group = document.getElementById('groupFilter').value;
            var childClass = document.getElementById('classFilter').value;
            var status = document.getElementById('statusFilter').value;
            var duplicatesOnly = document.getElementById('duplicatesOnly').checked;
            var rows = document.querySelectorAll('#childrenTable tbody tr');

            rows.forEach(function (row) {
                var matches = row.textContent.toLowerCase().includes(value)
                    && (!group || row.dataset.groupId === group)
                    && (!childClass || row.dataset.classId === childClass)
                    && (!status || row.dataset.status === status)
                    && (!duplicatesOnly || row.dataset.duplicate === '1');
                row.style.display = matches ? '' : 'none';
            });
        }

        ['search', 'groupFilter', 'classFilter', 'statusFilter', 'duplicatesOnly'].forEach(function (id) {
            document.getElementById(id).addEventListener('input', filterChildren);
            document.getElementById(id).addEventListener('change', filterChildren);
        });
    </script>
@endsection
