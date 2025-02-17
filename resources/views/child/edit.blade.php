@extends('layouts.app')

@section('content')
    <div class="container">
        @can('edit schickzeiten')
            <a href="{{ route('child.index') }}" class="btn btn-primary">Zurück</a>
        @else
            <a href="{{ url('einstellungen') }}" class="btn btn-primary">Zurück</a>
        @endcan
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Bearbeiten</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('child.update', $child->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="first_name" class="form-label">Vorname</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                               value="{{ old('first_name', $child->first_name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="last_name" class="form-label">Nachname</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                               value="{{ old('last_name', $child->last_name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="group_id" class="form-label">Gruppe</label>
                        <select class="custom-select" id="group_id" name="group_id" required>
                            <option disabled selected>Wähle eine Gruppe</option>
                            @foreach($groups as $group)
                                <option
                                    value="{{ $group->id }}" {{ $child->group_id == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="class_id" class="form-label">Klassenstufe</label>
                        <select class="custom-select" id="class_id" name="class_id" required>
                            <option disabled selected>Wähle eine Klassenstufe</option>
                            @foreach($groups as $group)
                                <option
                                    value="{{ $group->id }}" {{ $child->class_id == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Elternteil</label>
                        <select class="custom-select" id="parent_id" name="parent_id" required>
                            <option disabled selected>Wähle einen Elternteil</option>
                            @foreach($parents as $parent)
                                <option
                                    value="{{ $parent->id }}" {{ $child->parents->contains($parent->id) ? 'selected' : '' }}>{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Speichern</button>
                </form>
            </div>
        </div>
    </div>
@endsection
