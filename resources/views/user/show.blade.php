@extends('layouts.app')
@section('title') - Benutzer @endsection

@section('content')
<form action="{{ url('/users/').'/'.$user->id }}" method="post" id="user-edit-form">
    @csrf
    @method('PUT')

    <div class="container-fluid">

        {{-- ══ Seitenkopf ══ --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ url('/users') }}"
                   class="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-lg border transition-colors"
                   style="border-color: var(--color-card-border); color: var(--color-text-secondary);"
                   title="Zurück zur Übersicht">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="min-w-0">
                    <h1 class="text-xl font-bold leading-tight truncate" style="color: var(--color-text-primary);">
                        {{ $user->name }}
                    </h1>
                    <p class="text-sm truncate" style="color: var(--color-text-secondary);">
                        {{ $user->email }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                {{-- Willkommens-E-Mail erneut versenden --}}
                @can('edit user')
                    <button type="button"
                            onclick="confirmResendWelcome()"
                            class="btn btn-outline-info btn-sm"
                            title="Neues Kennwort generieren und Zugangsdaten erneut per E-Mail versenden">
                        <i class="fas fa-paper-plane"></i>
                        <span class="hidden sm:inline">Zugangsdaten erneut senden</span>
                    </button>
                @endcan

                {{-- Speichern-Button (Desktop, oben rechts – wird per JS eingeblendet) --}}
                <button type="submit"
                        form="user-edit-form"
                        class="btn btn-success btn-sm"
                        id="btn-save-desktop"
                        style="display: none;">
                    <i class="fas fa-save"></i>
                    <span class="hidden sm:inline">Änderungen speichern</span>
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ══ Haupt-Layout: auf Mobile gestapelt, auf Desktop nebeneinander ══ --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

            {{-- ─── Spalte 1: Benutzer-Einstellungen (lg: 6 von 12) ─── --}}
            <div class="lg:col-span-6">
                <div class="card h-full">
                    <div class="card-header">
                        <h5 class="card-title mb-0 flex items-center gap-2">
                            <i class="fas fa-user-cog" style="color: var(--color-primary);"></i>
                            Benutzer-Einstellungen
                        </h5>
                    </div>
                    <div class="card-body space-y-4">

                        {{-- Name --}}
                        <div class="form-group mb-0">
                            <label class="label-control">Name</label>
                            <input type="text" class="form-control" placeholder="Name"
                                   name="name" value="{{ $user->name }}" required>
                        </div>

                        {{-- E-Mail --}}
                        <div class="form-group mb-0">
                            <label class="label-control">E-Mail</label>
                            <input type="email" class="form-control" placeholder="E-Mail"
                                   name="email" value="{{ $user->email }}" required>
                        </div>

                        {{-- Öffentliche Kontaktdaten --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group mb-0">
                                <label class="label-control">Öffentliche E-Mail
                                    <span class="text-xs font-normal" style="color: var(--color-text-muted);">(für Eltern in gleichen Gruppen sichtbar)</span>
                                </label>
                                <input type="email" class="form-control" placeholder="öffentliche E-Mail"
                                       name="publicMail" value="{{ $user->publicMail }}" autocomplete="off">
                            </div>
                            <div class="form-group mb-0">
                                <label class="label-control">Öffentliche Telefonnummer
                                    <span class="text-xs font-normal" style="color: var(--color-text-muted);">(für Eltern sichtbar)</span>
                                </label>
                                <input type="tel" class="form-control" placeholder="Telefonnummer"
                                       name="publicPhone" value="{{ $user->publicPhone }}" autocomplete="off">
                            </div>
                        </div>

                        {{-- Benachrichtigung & Kopie --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group mb-0">
                                <label class="label-control">
                                    Benachrichtigung per E-Mail
                                    @if($user->lastEmail)
                                        <span class="text-xs font-normal" style="color: var(--color-text-muted);">
                                            (letzte: {{ $user->lastEmail->format('d.m.Y') }})
                                        </span>
                                    @endif
                                </label>
                                <select class="custom-select" name="benachrichtigung">
                                    <option value="daily"   @if($user->benachrichtigung == 'daily')   selected @endif>Täglich</option>
                                    <option value="weekly"  @if($user->benachrichtigung == 'weekly')  selected @endif>Wöchentlich (Freitags)</option>
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="label-control">Rückmeldungs-Kopie</label>
                                <select class="custom-select" name="sendCopy">
                                    <option value="1" @if($user->sendCopy == 1) selected @endif>Kopie erhalten</option>
                                    <option value="0" @if($user->sendCopy == 0) selected @endif>Keine Kopie</option>
                                </select>
                            </div>
                        </div>

                        {{-- Passwort ändern & Konto-Status --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group mb-0">
                                <label class="label-control">Muss Passwort ändern</label>
                                <select class="custom-select" name="changePassword">
                                    <option value="1" @if($user->changePassword)  selected @endif>Ja</option>
                                    <option value="0" @if(!$user->changePassword) selected @endif>Nein</option>
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="label-control">
                                    Konto-Status
                                    @if(!$user->is_active)
                                        <span class="badge badge-danger">Deaktiviert</span>
                                    @endif
                                </label>
                                <select class="custom-select" name="is_active">
                                    <option value="1" @if($user->is_active !== false) selected @endif>Aktiv</option>
                                    <option value="0" @if($user->is_active === false) selected @endif>Deaktiviert</option>
                                </select>
                                @if($user->is_active === false && $user->deactivated_at)
                                    <small class="form-text">
                                        Deaktiviert am: {{ $user->deactivated_at->format('d.m.Y H:i') }}
                                    </small>
                                @else
                                    <small class="form-text">Deaktivierte Benutzer werden automatisch ausgeloggt.</small>
                                @endif
                            </div>
                        </div>

                        {{-- Neues Passwort (Admin) --}}
                        @can('set password')
                            <div class="form-group mb-0">
                                <label class="label-control">
                                    Neues Passwort
                                    <span class="text-xs font-normal" style="color: var(--color-text-muted);">(mind. 10 Zeichen, Groß-/Kleinbuchstaben und Zahl)</span>
                                </label>
                                <input class="form-control" name="new-password" type="password"
                                       minlength="10" autocomplete="new-password">
                            </div>
                        @endcan

                        {{-- Verknüpfung (Sorgeberechtiger 2) --}}
                        <div class="form-group mb-0">
                            @if($user->sorg2 != "")
                                <label class="label-control">Verknüpft mit</label>
                                <div class="flex items-center gap-3 p-3 rounded-lg border"
                                     style="background: var(--color-body-bg); border-color: var(--color-card-border);">
                                    <i class="fas fa-link" style="color: var(--color-primary);"></i>
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ url('users/'.$user->sorg2) }}"
                                           class="font-medium" style="color: var(--color-primary);">
                                            {{ $user->sorgeberechtigter2?->name }}
                                        </a>
                                    </div>
                                    <a href="{{ url('users/'.$user->id.'/remove/sorg2/'.$user->sorg2) }}"
                                       class="btn btn-sm btn-outline-danger"
                                       title="Verknüpfung aufheben">
                                        <i class="fas fa-unlink"></i>
                                        <span class="hidden sm:inline">Verknüpfung aufheben</span>
                                    </a>
                                </div>
                            @else
                                <label class="label-control" for="sorg2">Verknüpfen mit:</label>
                                <select class="custom-select" name="sorg2" id="sorg2">
                                    <option value="">– Keinen auswählen –</option>
                                    @foreach($users as $otherUser)
                                        <option value="{{ $otherUser->id }}">{{ $otherUser->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

            {{-- ─── Spalte 2: Gruppen, Rollen, Rechte (lg: 6 von 12) ─── --}}
            <div class="lg:col-span-6 grid grid-cols-1 sm:grid-cols-3 gap-4 content-start">

                {{-- Gruppen --}}
                <div class="sm:col-span-3 md:col-span-1 card">
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
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox"
                                               id="role_{{ $role->name }}"
                                               name="roles[]"
                                               value="{{ $role->name }}"
                                               class="w-5 h-5 rounded cursor-pointer flex-shrink-0"
                                               style="accent-color: var(--color-primary);"
                                               @if($user->hasRole($role->name)) checked @endif>
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

                {{-- Individuelle Rechte --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0 flex items-center gap-2 text-sm">
                            <i class="fas fa-key" style="color: var(--color-widget-accent-from);"></i>
                            Indiv. Rechte
                        </h5>
                    </div>
                    <div class="card-body">
                        @can('edit permission')
                            <div class="space-y-2">
                                @foreach($permissions as $permission)
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox"
                                               id="perm_{{ $permission->name }}"
                                               name="permissions[]"
                                               value="{{ $permission->name }}"
                                               class="w-5 h-5 rounded cursor-pointer flex-shrink-0"
                                               style="accent-color: var(--color-primary);"
                                               @if($user->hasDirectPermission($permission->name)) checked @endif>
                                        <span class="text-sm select-none" style="color: var(--color-text-primary);">
                                            {{ $permission->name }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm" style="color: var(--color-text-secondary);">Kein Recht zur Rechtevergabe</p>
                        @endcan
                    </div>
                </div>

            </div>

        </div>

        {{-- ══ Speichern-Button (inline, unter allem) ══ --}}
        <div class="mt-4" id="save-btn-container" style="display: none;">
            <button type="submit" class="btn btn-success w-full md:w-auto" id="btn-save">
                <i class="fas fa-save"></i> Änderungen speichern
            </button>
        </div>

    </div>
</form>

{{-- Verstecktes Formular für das erneute Versenden der Willkommens-E-Mail --}}
{{-- MUSS außerhalb von user-edit-form stehen, da verschachtelte <form>-Elemente in HTML ungültig sind --}}
@can('edit user')
<form id="resend-welcome-form"
      action="{{ route('users.resendWelcome', $user) }}"
      method="POST"
      class="hidden">
    @csrf
</form>
@endcan

{{-- Sticky Save-Bar auf Mobile (erscheint nach Änderungen) --}}
<div id="sticky-save-bar"
     class="fixed bottom-0 left-0 right-0 md:hidden px-4 py-3 border-t shadow-2xl z-50 flex items-center gap-3"
     style="display: none !important; background: var(--color-card-bg); border-color: var(--color-card-border);">
    <span class="flex-1 text-sm font-medium" style="color: var(--color-text-secondary);">
        <i class="fas fa-exclamation-circle text-yellow-500 mr-1"></i>
        Ungespeicherte Änderungen
    </span>
    <button type="submit" form="user-edit-form" class="btn btn-success">
        <i class="fas fa-save"></i> Speichern
    </button>
</div>

@endsection

@push('js')
<script src="{{ asset('js/plugins/sweetalert2.all.min.js') }}"></script>
<script>
/**
 * Bestätigungsdialog vor dem erneuten Versenden der Willkommens-E-Mail.
 * Verwendet SweetAlert2 falls geladen, sonst nativen confirm()-Dialog.
 */
function confirmResendWelcome() {
    const userName  = @json($user->name);
    const userEmail = @json($user->email);

    function doSubmit() {
        document.getElementById('resend-welcome-form').submit();
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Zugangsdaten erneut versenden?',
            html:
                '<p>Für <strong>' + userName + '</strong> (<code>' + userEmail + '</code>) wird</p>' +
                '<ul style="text-align:left;margin-top:10px;">' +
                '<li>ein <strong>neues Kennwort</strong> generiert,</li>' +
                '<li>das bisherige Kennwort <strong>sofort ungültig</strong> gemacht,</li>' +
                '<li>eine E-Mail mit den neuen Zugangsdaten versendet.</li>' +
                '</ul>',
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-paper-plane"></i> Ja, jetzt versenden',
            cancelButtonText:  'Abbrechen',
            confirmButtonColor: '#0891b2',
        }).then(function(result) {
            // SweetAlert2 v8: result.value ist gesetzt bei Bestätigung
            // SweetAlert2 v9+: result.isConfirmed
            if (result.value || result.isConfirmed) {
                doSubmit();
            }
        });
    } else {
        if (confirm(
            'Zugangsdaten erneut an ' + userEmail + ' versenden?\n\n' +
            'Das bisherige Passwort wird sofort ungültig. ' +
            'Ein neues Kennwort wird generiert und per E-Mail verschickt.'
        )) {
            console.log()
            doSubmit();
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    let formChanged = false;
    const saveContainer  = document.getElementById('save-btn-container');
    const stickySaveBar  = document.getElementById('sticky-save-bar');
    const btnSaveDesktop = document.getElementById('btn-save-desktop');

    function onFormChange() {
        if (!formChanged) {
            formChanged = true;
            // Desktop: Top-Button + inline Button einblenden
            if (saveContainer)  saveContainer.style.display  = '';
            if (btnSaveDesktop) btnSaveDesktop.style.display  = '';
            // Mobile: sticky Bar einblenden
            if (stickySaveBar) {
                stickySaveBar.style.setProperty('display', 'flex', 'important');
                const content = document.querySelector('.main-panel > .content');
                if (content) content.style.paddingBottom = '80px';
            }
        }
    }

    document.getElementById('user-edit-form')?.querySelectorAll('input, select, textarea').forEach(el => {
        el.addEventListener('input',  onFormChange);
        el.addEventListener('change', onFormChange);
    });
});
</script>
@endpush
