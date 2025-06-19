@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Nutzer ohne Gruppe</h5>
        </div>
        <div class="card-body">
            <p>Folgende Nutzer sind nach dem Schuljahreswechsel in keiner Gruppe mehr zugeordnet. Bitte prüfen Sie, ob diese Nutzer gelöscht werden sollen.</p>
            <form method="POST" action="{{ route('schoolyear.massDelete') }}">
                @csrf
                @method('delete')
                <div class="table-responsive mb-4">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Gruppen</th>
                                <th>Rollen</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($usersWithoutGroup as $user)
                            <tr>
                                <td>
                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" checked class="form-check-input" style="position: static; margin-left: 0;">
                                </td>
                                <td><strong>{{ $user->name }}</strong></td>
                                <td><span class="badge bg-secondary">{{ $user->email }}</span></td>
                                <td>
                                    @forelse($user->groups as $gruppe)
                                        <span class="badge bg-info text-dark me-1 mb-1">{{ $gruppe->name }}</span>
                                    @empty
                                        <span class="text-muted">Keine Gruppen</span>
                                    @endforelse
                                </td>
                                <td>
                                    @forelse($user->roles as $role)
                                        <span class="badge bg-warning text-dark me-1 mb-1">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-muted">Keine Rollen</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-danger btn-lg">
                        Ausgewählte Nutzer löschen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
