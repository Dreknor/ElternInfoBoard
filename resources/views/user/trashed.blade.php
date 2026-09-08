@extends('layouts.app')
@section('title') - Papierkorb (gelöschte Benutzer) @endsection

@section('content')

<div class="container-fluid">
    <div class="card">

        <div class="card-header border-bottom">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h5 class="card-title mb-0 flex items-center gap-2">
                    <i class="fas fa-trash-restore" style="color: var(--color-primary);"></i>
                    Papierkorb – gelöschte Benutzer
                </h5>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ url('users') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i>
                        <span class="hidden sm:inline">Zurück zur Übersicht</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">

            <p class="text-muted small mb-3">
                {{ $users->total() }} soft-gelöschte Benutzer.
                Diese Benutzer können wiederhergestellt oder endgültig gelöscht werden.
            </p>

            @if($users->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Es gibt aktuell keine gelöschten Benutzer.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Gelöscht am</th>
                                <th class="text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td class="font-medium" style="color: var(--color-text-primary);">
                                        {{ $user->name }}
                                    </td>
                                    <td class="text-sm" style="color: var(--color-text-secondary);">
                                        {{ $user->email }}
                                    </td>
                                    <td class="text-sm" style="color: var(--color-text-secondary);">
                                        {{ $user->deleted_at?->format('d.m.Y H:i') }}
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1">
                                            <form action="{{ url('users/trashed/'.$user->id.'/restore') }}" method="post" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-sm btn-success"
                                                        title="Benutzer wiederherstellen"
                                                        onclick="return confirm('Benutzer {{ addslashes($user->name) }} wirklich wiederherstellen?')">
                                                    <i class="fas fa-trash-restore"></i>
                                                    <span class="hidden md:inline">Wiederherstellen</span>
                                                </button>
                                            </form>
                                            <form action="{{ url('users/trashed/'.$user->id) }}" method="post" class="inline">
                                                @csrf
                                                @method('delete')
                                                <button type="submit"
                                                        class="btn btn-sm btn-danger"
                                                        title="Benutzer endgültig löschen"
                                                        onclick="return confirm('Benutzer {{ addslashes($user->name) }} wirklich ENDGÜLTIG löschen? Dies kann nicht rückgängig gemacht werden!')">
                                                    <i class="fas fa-eraser"></i>
                                                    <span class="hidden md:inline">Endgültig löschen</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            @endif

        </div>
    </div>
</div>

@endsection
