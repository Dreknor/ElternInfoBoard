@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Neue Arbeitsgemeinschaft erstellen</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('verwaltung.arbeitsgemeinschaften.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label">Name der AG</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Beschreibung</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="weekday" class="form-label">Wochentag</label>
                                    <select class="custom-select @error('weekday') is-invalid @enderror"
                                            id="weekday" name="weekday" required>
                                        <option value="">Bitte wählen...</option>
                                        @foreach($weekdays as $key => $day)
                                            <option value="{{ $key }}" {{ old('weekday') == $key ? 'selected' : '' }}>
                                                {{ $day }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('weekday')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="start_time" class="form-label">Startzeit</label>
                                    <input type="time" class="form-control @error('start_time') is-invalid @enderror"
                                           id="start_time" name="start_time" value="{{ old('start_time') }}" required>
                                    @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="end_time" class="form-label">Endzeit</label>
                                    <input type="time" class="form-control @error('end_time') is-invalid @enderror"
                                           id="end_time" name="end_time" value="{{ old('end_time') }}" required>
                                    @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">Startdatum</label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                           id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                                    @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">Enddatum</label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                           id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                                    @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="max_participants" class="form-label">Maximale Teilnehmerzahl</label>
                                <input type="number" class="form-control @error('max_participants') is-invalid @enderror"
                                       id="max_participants" name="max_participants"
                                       value="{{ old('max_participants') }}" min="1" required>
                                @error('max_participants')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="manager_id" class="form-label">Verantwortlicher</label>
                                <select class="custom-select @error('manager_id') is-invalid @enderror"
                                        id="manager_id" name="manager_id" required>
                                    <option value="">Bitte wählen...</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}"
                                            {{ old('manager_id') == $manager->id ? 'selected' : '' }}>
                                            {{ $manager->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('manager_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Zugangsberechtigte Gruppen</label>
                                <div class="row">
                                    @foreach($groups as $group)
                                        <div class="col-md-4">
                                            <div class="">
                                                <input  type="checkbox"
                                                       name="groups[]" value="{{ $group->id }}"
                                                       id="group_{{ $group->id }}"
                                                    {{ in_array($group->id, old('groups', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="group_{{ $group->id }}">
                                                    {{ $group->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('groups')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Arbeitsgemeinschaft erstellen</button>
                                <a href="{{ route('verwaltung.arbeitsgemeinschaften.index') }}" class="btn btn-secondary">Abbrechen</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
