@extends('layouts.app')

@section('content')
    <div class="container">
        <a href="{{ url('verwaltung/schickzeiten') }}" class="btn btn-primary">Zurück</a>
        <br>

        <div class="card">
            <div class="card-header">
                <h6 class="card-title">
                    Kind erstellen
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('child.store') }}">
                    @csrf
                    <div class="form-group">
                        <label for="first_name">Vorname</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                               value="{{$child->first_name ?? ''}}" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Nachname</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                               value="{{$child->last_name ?? ''}}" required>
                    </div>
                    <div class="form-group">
                        <label for="group_id">Gruppe</label>
                        <select class="form-control" id="group_id" name="group_id" required>
                            <option disabled selected>Wähle eine Gruppe</option>
                            @foreach($groups as $group)
                                <option value="{{$group->id}}">{{$group->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="class_id">Klassenstufe</label>
                        <select class="form-control" id="class_id" name="class_id" required>
                            <option disabled selected>Wähle eine Klassenstufe</option>
                            @foreach($groups as $group)
                                <option value="{{$group->id}}">{{$group->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="parent_one_id">Elternteil 1</label>
                        <select class="form-control" id="parent_id" name="parent_id" required>
                            @foreach($parents as $parent)
                                <option value="{{$parent->id}}">{{$parent->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Speichern</button>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#parent_id').select2({
                placeholder: 'Wähle einen Elternteil',
                allowClear: true
            });
        });
    </script>
@endpush
