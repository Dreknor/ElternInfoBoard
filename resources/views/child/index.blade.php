@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>Kinder</h3>
                <p>
                    Übersicht aller Kinder
                </p>
                <p>
                    <a href="{{ route('child.create') }}" class="btn btn-primary">Neues Kind anlegen</a>
                </p>


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
                            <td>
                                <a href="{{ route('child.edit', $child->id) }}" class="btn btn-primary btn-sm mb-1 mb-md-0">Edit</a>
                                <a href="{{ route('child.mandates.edit', $child->id) }}" class="btn btn-info btn-sm mb-1 mb-md-0">Vollmachten</a>


                                <form action="{{ route('child.destroy', $child->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>

                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <script>
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
