@extends('layouts.app')
@section('title') - Neuen Benutzer anlegen @endsection

@section('content')
<form action="{{ url('/users/') }}" method="post" autocomplete="off">
    @csrf

    <div class="container-fluid">

        {{-- ══ Seitenkopf ══ --}}
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ url('/users') }}"
               class="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-lg border transition-colors"
               style="border-color: var(--color-card-border); color: var(--color-text-secondary);"
               title="Zurück zur Übersicht">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-xl font-bold" style="color: var(--color-text-primary);">
                Neuen Benutzer anlegen
            </h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- ─── Spalte 1: Stammdaten ─── --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0 flex items-center gap-2">
                        <i class="fas fa-user-plus" style="color: var(--color-primary);"></i>
                        Benutzerdaten
                    </h5>
                </div>
                <div class="card-body space-y-4">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-group mb-0">
                        <label class="label-control">Name <span class="text-red-500">*</span></label>
                        <input type="text" class="form-control" placeholder="Vor- und Nachname"
                               name="name" required autocomplete="off" value="{{ old('name') }}">
                    </div>

                    <div class="form-group mb-0">
                        <label class="label-control">E-Mail-Adresse <span class="text-red-500">*</span></label>
                        <input type="email" class="form-control" placeholder="email@beispiel.de"
                               name="email" required autocomplete="off" value="{{ old('email') }}">
                        <small class="form-text">
                            <i class="fas fa-info-circle mr-1" style="color: var(--color-primary);"></i>
                            Der Benutzer erhält ein automatisch generiertes Startkennwort per E-Mail.
                        </small>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-success w-full" id="btn-save">
                            <i class="fas fa-save mr-1"></i>
                            Benutzer anlegen &amp; Startkennwort versenden
                        </button>
                    </div>

                </div>
            </div>

            {{-- ─── Spalte 2: Gruppen & Rollen ─── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 content-start">

                {{-- Gruppen --}}
                <div class="sm:col-span-2 md:col-span-1 card">
                    <div class="card-header">
                        <h5 class="card-title mb-0 flex items-center gap-2 text-sm">
                            <i class="fas fa-layer-group" style="color: var(--color-primary);"></i>
                            Gruppen
                        </h5>
                    </div>
                    <div class="card-body">
                        @include('include.formGroups')
                    </div>
                </div>

                {{-- Rollen --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0 flex items-center gap-2 text-sm">
                            <i class="fas fa-shield-alt" style="color: var(--color-widget-warning-from);"></i>
                            Rollen
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($roles->count() > 0)
                            <div class="space-y-2">
                                @foreach($roles as $role)
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox"
                                               id="role_{{ $role->name }}"
                                               name="roles[]"
                                               value="{{ $role->name }}"
                                               class="w-5 h-5 rounded cursor-pointer flex-shrink-0"
                                               style="accent-color: var(--color-primary);">
                                        <span class="text-sm select-none" style="color: var(--color-text-primary);">
                                            {{ $role->name }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm" style="color: var(--color-text-secondary);">Kein Recht zur Rollenzuordnung</p>
                        @endif
                    </div>
                </div>

            </div>

        </div>
    </div>
</form>
@endsection
