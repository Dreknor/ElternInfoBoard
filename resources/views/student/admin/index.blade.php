@extends('layouts.app')
@section('title') - Schülerkonten @endsection

@section('content')
<div class="container-fluid" x-data="{ selected: [] }">
    <div class="card">
        <div class="card-header border-bottom">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h5 class="card-title mb-0 flex items-center gap-2">
                    <i class="fas fa-user-graduate" style="color: var(--color-primary);"></i>
                    Schülerkonten
                </h5>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ url('users') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-users"></i><span class="hidden sm:inline">Benutzerkonten</span></a>
                    @can('import user')
                        <a href="{{ route('schuelerkonten.import') }}" class="btn btn-outline-info btn-sm"><i class="fas fa-file-import"></i><span class="hidden sm:inline">Schülerkonten-Import</span></a>
                    @endcan
                    @can('edit settings')
                        <a href="{{ route('schuelerkonten.settings') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-cog"></i><span class="hidden sm:inline">Einstellungen</span></a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card-body space-y-5">
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg border p-3" style="border-color: var(--color-card-border);"><div class="text-2xl font-bold">{{ $counts['konten'] }}</div><div class="text-xs" style="color: var(--color-text-secondary);">Konten</div></div>
                <div class="rounded-lg border p-3" style="border-color: var(--color-card-border);"><div class="text-2xl font-bold">{{ $counts['aktiv'] }}</div><div class="text-xs" style="color: var(--color-text-secondary);">aktiv</div></div>
                <div class="rounded-lg border p-3" style="border-color: var(--color-card-border);"><div class="text-2xl font-bold">{{ $counts['ohne'] }}</div><div class="text-xs" style="color: var(--color-text-secondary);">Kinder ohne Konto</div></div>
            </div>

            @if(! $settings->login_benutzername && ! $settings->login_sso)
                <div class="alert alert-warning text-sm">Weder SSO noch Benutzername-Login ist eingeschaltet. Schüler können sich so nicht anmelden.</div>
            @elseif(! $settings->login_benutzername)
                <div class="alert alert-info text-sm">Der Login mit Benutzername ist aus. Einmal-Codes und Zugangszettel funktionieren erst, wenn er in den Einstellungen eingeschaltet ist.</div>
            @endif

            {{-- Filter --}}
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="gruppe" class="form-label text-sm">Klasse / Gruppe</label>
                    <select id="gruppe" name="gruppe" class="form-control">
                        <option value="">alle</option>
                        @foreach($groups as $group)<option value="{{ $group->id }}" @selected($groupId === $group->id)>{{ $group->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="form-label text-sm">Anzeigen</label>
                    <select id="status" name="status" class="form-control">
                        <option value="alle" @selected($status === 'alle')>Kinder mit Konto</option>
                        <option value="aktiv" @selected($status === 'aktiv')>nur aktive Konten</option>
                        <option value="gesperrt" @selected($status === 'gesperrt')>nur gesperrte Konten</option>
                        <option value="ohne" @selected($status === 'ohne')>Kinder ohne Konto</option>
                    </select>
                </div>
                <div>
                    <label for="suche" class="form-label text-sm">Suche</label>
                    <input id="suche" name="suche" value="{{ $search }}" class="form-control" placeholder="Name oder Benutzername">
                </div>
                <div class="flex items-end"><button class="btn btn-primary w-full sm:w-auto"><i class="fas fa-filter"></i> Filtern</button></div>
            </form>

            @if($groupId && $status !== 'gesperrt')
                <form method="POST" action="{{ route('schuelerkonten.class') }}" class="rounded-lg border p-3 text-sm" style="border-color: var(--color-card-border);"
                      onsubmit="return confirm('Für alle Kinder dieser Klasse ohne Konto ein Konto anlegen und die Zugangszettel als PDF erzeugen?')">
                    @csrf
                    <input type="hidden" name="group_id" value="{{ $groupId }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span>Fehlende Konten dieser Klasse auf einmal anlegen. Das PDF enthält einen Zettel je neuem Konto.</span>
                        <button class="btn btn-success btn-sm"><i class="fas fa-users"></i> Konten für diese Klasse anlegen</button>
                    </div>
                </form>
            @endif

            {{-- Sammel-Aktion: Codes drucken --}}
            <form method="POST" action="{{ route('schuelerkonten.codes') }}" id="codes-form" class="flex flex-wrap items-center gap-2" x-show="selected.length > 0" x-cloak>
                @csrf
                <template x-for="id in selected" :key="id"><input type="hidden" name="student_ids[]" :value="id"></template>
                <span class="text-sm" x-text="selected.length + ' ausgewählt'"></span>
                <button class="btn btn-primary btn-sm" onclick="return confirm('Für die ausgewählten Konten neue Codes erzeugen? Alte Codes werden ungültig.')"><i class="fas fa-print"></i> Neue Codes drucken</button>
            </form>

            {{-- Liste: Tabelle ab Tablet, Karten auf dem Handy --}}
            <div class="hidden md:block">
                <table class="table table-hover w-full">
                    <thead>
                        <tr>
                            <th class="w-8"></th>
                            <th>Kind</th>
                            <th>Klasse</th>
                            <th>Benutzername</th>
                            <th>Status</th>
                            <th class="hidden lg:table-cell">Login</th>
                            <th class="text-right">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($children as $child)
                            @include('student.admin.partials.row', ['child' => $child, 'layout' => 'row'])
                        @empty
                            <tr><td colspan="7" class="text-center text-sm" style="color: var(--color-text-secondary);">Keine Einträge.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="space-y-3 md:hidden">
                @forelse($children as $child)
                    @include('student.admin.partials.row', ['child' => $child, 'layout' => 'card'])
                @empty
                    <p class="text-center text-sm" style="color: var(--color-text-secondary);">Keine Einträge.</p>
                @endforelse
            </div>
            <div>{{ $children->links() }}</div>

            @if($orphans->isNotEmpty())
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                    <h6 class="mb-2 font-semibold"><i class="fas fa-exclamation-triangle"></i> Konten ohne aktives Kind</h6>
                    <p class="mb-2">Diese Konten sind gesperrt und werden nach {{ $settings->loeschen_nach_tagen }} Tagen automatisch gelöscht.</p>
                    <ul class="space-y-1">
                        @foreach($orphans as $orphan)
                            <li class="flex flex-wrap items-center justify-between gap-2">
                                <span>{{ $orphan->name }} <span class="font-mono text-xs">({{ $orphan->username }})</span></span>
                                <form method="POST" action="{{ route('schuelerkonten.destroy', $orphan->id) }}" onsubmit="return confirm('Konto endgültig löschen?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> Jetzt löschen</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
