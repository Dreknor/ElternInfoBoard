@extends('layouts.app')
@section('title') - Benutzer @endsection

@section('content')

<div class="container-fluid">
    <div class="card">

        {{-- ══ Card-Header: Titel + Aktions-Buttons ══ --}}
        <div class="card-header border-bottom">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h5 class="card-title mb-0 flex items-center gap-2">
                    <i class="fas fa-users" style="color: var(--color-primary);"></i>
                    Benutzerkonten
                </h5>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ url('users/create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-user-plus"></i>
                        <span class="hidden sm:inline">Benutzer anlegen</span>
                    </a>
                    @can('import user')
                        <a href="{{ url('users/import') }}" class="btn btn-outline-info btn-sm">
                            <i class="far fa-address-book"></i>
                            <span class="hidden sm:inline">Importieren</span>
                        </a>
                        <a href="{{ url('users/importVerein') }}" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-users"></i>
                            <span class="hidden md:inline">Vereinsmitglieder</span>
                            <span class="md:hidden hidden sm:inline">Verein</span>
                        </a>
                    @endcan
                    @can('edit user')
                        <a href="{{ url('users/mass/delete') }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-trash-alt"></i>
                            <span class="hidden md:inline">Mehrere löschen</span>
                        </a>
                        <a href="{{ url('users/vereinsmitglieder/non-members') }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-user-slash"></i>
                            <span class="hidden md:inline">Nicht-Vereinsmitglieder</span>
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card-body">

            {{-- ══ Such- und Filterformular ══ --}}
            <form method="get" action="{{ url('users') }}" class="mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="label-control">Suche (Name / E-Mail)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Name oder E-Mail…"
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    <div>
                        <label class="label-control">Rolle</label>
                        <select name="role" class="custom-select">
                            <option value="">– Alle Rollen –</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label-control">Gruppe</label>
                        <select name="group" class="custom-select">
                            <option value="">– Alle Gruppen –</option>
                            @foreach($groups as $gruppe)
                                <option value="{{ $gruppe->id }}" @selected(request('group') == $gruppe->id)>
                                    {{ $gruppe->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-1">
                            <i class="fas fa-filter"></i>
                            <span class="hidden sm:inline">Filtern</span>
                        </button>
                        @if(request()->hasAny(['search','role','group']))
                            <a href="{{ url('users') }}" class="btn btn-outline-secondary" title="Filter zurücksetzen">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Ergebnisanzeige --}}
            <p class="text-muted small mb-3">
                {{ $users->total() }} Benutzer gefunden
                @if(request()->hasAny(['search','role','group']))
                    <span class="badge badge-info ml-1">gefiltert</span>
                @endif
            </p>

            {{-- ══ DESKTOP-ANSICHT: Tabelle (ab md) ══ --}}
            <div class="hidden md:block">
                <div class="table-responsive">
                    <table class="table table-hover" id="userTable">
                        <thead>
                            <tr>
                                <th class="w-8"></th>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Gruppen</th>
                                <th>Rechte</th>
                                <th class="hidden lg:table-cell">Verknüpft</th>
                                <th class="hidden lg:table-cell">E-Mail-Status</th>
                                <th class="text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr @if($user->is_active === false) class="opacity-60" title="Konto deaktiviert" @endif>
                                    <td>
                                        <a href="{{ url('/users/'.$user->id) }}" class="text-blue-600 hover:text-blue-800" title="Details anzeigen">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                    <td class="font-medium" style="color: var(--color-text-primary);">
                                        {{ $user->name }}
                                        @if($user->is_active === false)
                                            <span class="badge badge-danger ml-1"><i class="fas fa-ban"></i> Inaktiv</span>
                                        @endif
                                    </td>
                                    <td class="text-sm" style="color: var(--color-text-secondary);">
                                        {{ $user->email }}
                                    </td>
                                    <td class="small">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($user->groups as $gruppe)
                                                <span class="badge badge-info">{{ $gruppe->name }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="small">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($user->roles as $role)
                                                <span class="badge badge-warning">{{ $role->name }}</span>
                                            @endforeach
                                            @foreach($user->permissions as $permission)
                                                <span class="badge badge-danger">{{ $permission->name }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="hidden lg:table-cell text-sm" style="color: var(--color-text-secondary);">
                                        {{ $user->sorgeberechtigter2?->name }}
                                    </td>
                                    <td class="hidden lg:table-cell">
                                        <a class="btn btn-sm @if(is_null($user->lastEmail) or $user->lastEmail->lessThan(\Carbon\Carbon::parse('last friday'))) btn-outline-danger @else btn-outline-success @endif"
                                           href="{{ url('email/daily/'.$user->id) }}"
                                           title="Letzte Mail: {{ $user->lastEmail?->format('d.m.Y') ?? 'nie' }}">
                                            <i class="fas fa-envelope"></i>
                                            <span class="hidden xl:inline">{{ $user->lastEmail?->format('d.m.Y') ?? 'nie' }}</span>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1">
                                            <form action="{{ url('users').'/'.$user->id }}" method="post" class="inline">
                                                @csrf
                                                @method('delete')
                                                <button type="submit"
                                                        class="btn btn-sm btn-danger user_ajax-delete"
                                                        data-id="{{ $user->id }}"
                                                        title="Benutzer löschen"
                                                        onclick="return confirm('Benutzer {{ addslashes($user->name) }} wirklich löschen?')">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            </form>
                                            @can('loginAsUser')
                                                <form method="POST" action="{{ url('showUser/'.$user->id) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-info" title="Als dieser Benutzer anmelden">
                                                        <i class="fas fa-sign-in-alt"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ══ MOBILE-ANSICHT: Karten (bis md) ══ --}}
            <div class="md:hidden space-y-3">
                @foreach($users as $mobileUser)
                    <div class="rounded-lg border overflow-hidden @if($mobileUser->is_active === false) opacity-70 @endif"
                         style="background: var(--color-card-bg); border-color: var(--color-card-border);">

                        {{-- Karten-Header --}}
                        <div class="flex items-center justify-between px-4 py-3 border-b"
                             style="background: var(--color-body-bg); border-color: var(--color-card-border);">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-base" style="color: var(--color-text-primary);">
                                        {{ $mobileUser->name }}
                                    </span>
                                    @if($mobileUser->is_active === false)
                                        <span class="badge badge-danger"><i class="fas fa-ban"></i> Inaktiv</span>
                                    @endif
                                </div>
                                <div class="text-sm mt-0.5 truncate" style="color: var(--color-text-secondary);">
                                    {{ $mobileUser->email }}
                                </div>
                            </div>
                            {{-- Primäre Aktion: Detail-Link --}}
                            <a href="{{ url('/users/'.$mobileUser->id) }}"
                               class="flex-shrink-0 ml-2 flex items-center justify-center w-10 h-10 rounded-full text-white"
                               style="background: var(--color-primary);"
                               title="Details bearbeiten">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>

                        {{-- Karten-Body: Gruppen & Rollen --}}
                        @if($mobileUser->groups->count() > 0 || $mobileUser->roles->count() > 0 || $mobileUser->permissions->count() > 0)
                            <div class="px-4 py-2 border-b" style="border-color: var(--color-card-border);">
                                @if($mobileUser->groups->count() > 0)
                                    <div class="flex flex-wrap gap-1 mb-1">
                                        @foreach($mobileUser->groups as $gruppe)
                                            <span class="badge badge-info">{{ $gruppe->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($mobileUser->roles->count() > 0 || $mobileUser->permissions->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($mobileUser->roles as $role)
                                            <span class="badge badge-warning">{{ $role->name }}</span>
                                        @endforeach
                                        @foreach($mobileUser->permissions as $permission)
                                            <span class="badge badge-danger">{{ $permission->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Karten-Footer: Aktions-Buttons --}}
                        <div class="flex items-center justify-between px-4 py-2 gap-2">
                            {{-- E-Mail-Status --}}
                            <a class="btn btn-sm flex-1 @if(is_null($mobileUser->lastEmail) or $mobileUser->lastEmail->lessThan(\Carbon\Carbon::parse('last friday'))) btn-outline-danger @else btn-outline-success @endif"
                               href="{{ url('email/daily/'.$mobileUser->id) }}"
                               title="Letzte Mail: {{ $mobileUser->lastEmail?->format('d.m.Y') ?? 'nie' }}">
                                <i class="fas fa-envelope mr-1"></i>
                                <span class="text-xs">{{ $mobileUser->lastEmail?->format('d.m.Y') ?? 'keine Mail' }}</span>
                            </a>

                            <div class="flex items-center gap-2 flex-shrink-0">
                                @can('loginAsUser')
                                    <form method="POST" action="{{ url('showUser/'.$mobileUser->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-info" title="Als dieser Benutzer anmelden">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </button>
                                    </form>
                                @endcan

                                <form action="{{ url('users').'/'.$mobileUser->id }}" method="post" class="inline">
                                    @csrf
                                    @method('delete')
                                    <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            title="Benutzer löschen"
                                            onclick="return confirm('Benutzer {{ addslashes($mobileUser->name) }} wirklich löschen?')">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $users->links() }}
            </div>

        </div>
    </div>
</div>

@endsection

@push('js')
@can('edit user')
<script src="{{ asset('js/plugins/sweetalert2.all.min.js') }}"></script>
<script>
    $('.user_ajax-delete').on('click', function (e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var name = $(this).closest('tr').find('td:nth-child(2)').text().trim()
                || $(this).closest('.rounded-lg').find('.font-semibold').first().text().trim();

        swal.fire({
            title: "Benutzer wirklich entfernen?",
            text: name ? '"' + name + '" wird gelöscht.' : '',
            icon: "warning",
            showCancelButton: true,
            cancelButtonText: "Abbrechen",
            confirmButtonText: "Ja, löschen",
            confirmButtonColor: "#dc2626"
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>
@endcan
@endpush


