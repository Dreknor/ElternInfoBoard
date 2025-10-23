@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>Vollmachten für {{ $child->first_name }} {{ $child->last_name }}</h3>
                <p>
                    Übersicht aller Vollmachten
                </p>
                <a href="{{ route('child.index') }}" class="btn btn-secondary">Zurück zur Kinderübersicht</a>
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="mandatesTable">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Beschreibung</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($child->mandates as $mandate)
                        <tr>
                            <td>{{ $mandate->mandate_name }}</td>
                            <td>{{ $mandate->mandate_description }}</td>
                            <td>
                                <form action="{{ route('child.mandates.destroy', [$child->id, $mandate->id]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>


            </div>
            <div class="card-footer">
                <form action="{{ route('child.mandates.store', $child->id) }}" method="POST" class="form-inline">
                    @csrf
                    @method('PUT')
                    <textarea name="mandates" class="form-control mb-2 mr-sm-2 w-100" placeholder="Vollmachten - jede Zeile eine neue Vollmacht. Erst der Name, nach dem Komma Hinweise"></textarea>
                    <button type="submit" class="btn btn-primary mb-2">Vollmachten speichern</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('search').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let rows = document.querySelectorAll('#childrenTable tbody tr');
            rows.forEach(row => {
                let text = row.textContent.toUpperCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    </script>
@endsection
