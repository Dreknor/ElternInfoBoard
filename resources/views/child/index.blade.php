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


                <input type="text" class="form-control" id="search" placeholder="Search for names..">
            </div>
            <div class="card-body">
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
                        <tr>
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
                                    {{ $parent?->name }}@if($parent->sorgeberechtigter2)
                                        ,  {{$parent->sorgeberechtigter2->name}}
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                <a href="{{ route('child.edit', $child->id) }}" class="btn btn-primary">Edit</a>
                                <a href="{{ route('child.mandates.edit', $child->id) }}" class="btn btn-info">Vollmachten</a>


                                <form action="{{ route('child.destroy', $child->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </td>

                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('search').addEventListener('keyup', function () {
            var value = this.value.toLowerCase();
            var rows = document.querySelectorAll('#childrenTable tbody tr');
            rows.forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(value) ? '' : 'none';
            });
        });
    </script>
@endsection
