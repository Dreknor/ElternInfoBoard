@extends('layouts.app')

@section('title') - Familien @endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3>
                    Familien
                    @if(config('family.resolver') === 'child_centric')
                        <span class="badge badge-success" title="FAMILY_RESOLVER=child_centric">kind-zentriertes Modell aktiv</span>
                    @else
                        <span class="badge badge-secondary" title="FAMILY_RESOLVER=legacy – Umschalten nach Prüfung mit php artisan family:status">Übergangsmodus (Kontoverknüpfung)</span>
                    @endif
                </h3>
                <p class="text-muted mb-2">
                    Familien sind die Einheit für Pflichtstunden, Reinigung, Lesebestätigungen und Rückmeldungen je Familie.
                    Zugriff auf Kinder entsteht ausschließlich über die Beziehung zum Kind.
                </p>
                <div class="d-flex flex-wrap align-items-center">
                    <a href="{{ route('families.review') }}" class="btn btn-outline-warning mr-2 mb-2">
                        Prüfen
                        <span class="badge badge-warning">{{ $counters['review_cases'] + $counters['pending_links'] + $counters['open_reports'] }}</span>
                    </a>
                    <form action="{{ route('families.rebuild') }}" method="POST" class="mr-2 mb-2"
                          onsubmit="return confirm('Familien automatisch bilden? Gesperrte Familien bleiben unverändert, Klärungsfälle werden nur gemeldet.')">
                        @csrf
                        <input type="hidden" name="only_unassigned" value="1">
                        <button class="btn btn-outline-primary">Personen ohne Familie zuordnen ({{ $counters['without_family'] }})</button>
                    </form>
                    <form action="{{ route('families.index') }}" method="GET" class="form-inline ml-auto mb-2">
                        <input type="text" name="search" class="form-control mr-2" placeholder="Familie oder Person suchen" value="{{ $search }}">
                        <label class="mr-2"><input type="checkbox" name="locked" value="1" @checked(request()->boolean('locked'))> nur gesperrte</label>
                        <button class="btn btn-primary">Suchen</button>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Familie</th>
                        <th>Mitglieder</th>
                        <th>Herkunft</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($families as $family)
                        <tr>
                            <td>{{ $family->name }}</td>
                            <td>{{ $family->users->pluck('name')->implode(', ') }}</td>
                            <td><small>{{ $family->source }}</small></td>
                            <td>
                                @if($family->is_locked)
                                    <span class="badge badge-secondary" title="Von der Verwaltung gepflegt – die Automatik ändert diese Familie nicht">gesperrt</span>
                                @endif
                            </td>
                            <td><a href="{{ route('families.show', $family) }}" class="btn btn-sm btn-primary">Öffnen</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">Keine Familien gefunden.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $families->links() }}
            </div>
        </div>
    </div>
@endsection
