@extends('layouts.app')

@section('title') - {{ $family->name }} @endsection

@section('content')
    <div class="container-fluid">
        <a href="{{ route('families.index') }}" class="btn btn-primary mb-2">Zurück</a>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h4 class="mb-0">{{ $family->name }}</h4></div>
                    <div class="card-body">
                        <form action="{{ route('families.update', $family) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $family->name }}" required>
                            </div>
                            <div class="form-group">
                                <label>Notizen (nur Verwaltung)</label>
                                <textarea name="notes" class="form-control no-tinymce" rows="2">{{ $family->notes }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_locked" value="1" @checked($family->is_locked)>
                                    gesperrt – die automatische Familienbildung ändert diese Familie nicht
                                </label>
                            </div>
                            <button class="btn btn-success">Speichern</button>
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header"><h5 class="mb-0">Mitglieder</h5></div>
                    <form action="{{ route('families.split', $family) }}" method="POST" id="split-form">
                        @csrf
                    </form>
                    <ul class="list-group list-group-flush">
                        @foreach($family->users as $member)
                            <li class="list-group-item d-flex align-items-center">
                                <input type="checkbox" name="user_ids[]" value="{{ $member->id }}" form="split-form" class="mr-2" title="für „Trennen“ auswählen">
                                <a href="{{ url('users/'.$member->id) }}" class="mr-auto">{{ $member->name }}</a>
                                <small class="text-muted mr-3">{{ $member->email }}</small>
                                <form action="{{ route('families.members.remove', [$family, $member]) }}" method="POST"
                                      onsubmit="return confirm('{{ addslashes($member->name) }} aus der Familie lösen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">lösen</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                    <div class="card-footer">
                        <form action="{{ route('families.members.add', $family) }}" method="POST" class="form-inline mb-2">
                            @csrf
                            <select name="user_id" class="custom-select mr-2" required>
                                <option value="">Person ohne Familie hinzufügen …</option>
                                @foreach($candidates as $candidate)
                                    <option value="{{ $candidate->id }}">{{ $candidate->name }} ({{ $candidate->email }})</option>
                                @endforeach
                            </select>
                            <button class="btn btn-primary">Hinzufügen</button>
                        </form>
                        <div class="form-inline">
                            <input type="text" name="name" class="form-control mr-2" placeholder="Name der neuen Familie" form="split-form">
                            <button class="btn btn-outline-secondary" form="split-form"
                                    onclick="return confirm('Ausgewählte Mitglieder in eine neue Familie trennen?')">Ausgewählte trennen</button>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header"><h5 class="mb-0">Mit anderer Familie zusammenführen</h5></div>
                    <div class="card-body">
                        <form action="{{ route('families.merge', $family) }}" method="POST" class="form-inline"
                              onsubmit="return confirm('Die gewählte Familie wird in diese Familie übernommen. Fortfahren?')">
                            @csrf
                            <select name="source_id" class="custom-select mr-2" required>
                                <option value="">Familie wählen …</option>
                                @foreach($otherFamilies as $other)
                                    <option value="{{ $other->id }}">{{ $other->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-warning">Zusammenführen</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Kinder der Familienmitglieder</h5>
                        <small class="text-muted">Abgeleitet über die Beziehungen der Mitglieder – bearbeiten am Kind.</small>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse($children as $child)
                            <li class="list-group-item">
                                <a href="{{ route('child.edit', $child) }}#bezugspersonen"><strong>{{ $child->first_name }} {{ $child->last_name }}</strong></a>
                                <small class="text-muted">{{ $child->class?->name }} {{ $child->group?->name }}</small>
                                <div class="small">
                                    @foreach($child->parents as $guardian)
                                        <span @class(['badge', 'badge-light' => $guardian->family_id === $family->id, 'badge-info' => $guardian->family_id !== $family->id])>
                                            {{ $guardian->name }} · {{ $guardian->pivot->relationType()->label() }}
                                        </span>
                                    @endforeach
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Keine Kinder verknüpft.</li>
                        @endforelse
                    </ul>
                    <div class="card-footer small text-muted">
                        Blau markierte Bezugspersonen gehören zu einer anderen Familie (z. B. getrennt lebende Eltern).
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
