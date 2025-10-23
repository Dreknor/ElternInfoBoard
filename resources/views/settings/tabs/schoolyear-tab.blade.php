<div class="tab-pane" id="schoolyear" role="tabpanel" aria-labelledby="schoolyear-tab">
    <div class="container-fluid">
        <div class="card mt-4">
            <div class="card-header bg-warning text-dark">
                <strong>Achtung!</strong> Der Schuljahreswechsel ist ein kritischer Vorgang. Bitte stellen Sie sicher, dass alle Einstellungen und Gruppen korrekt sind, bevor Sie fortfahren.
            </div>
            <div class="card-body">
                <h1>Schuljahreswechsel</h1>
                <p>Hier können Sie den Schuljahreswechsel manuell starten. Bitte prüfen Sie alle Einstellungen und Gruppen sorgfältig, bevor Sie fortfahren.</p>
                <form method="POST" action="{{ route('schoolyear.process') }}">
                    @csrf
                    <h3>Gruppen-Zuordnung</h3>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Alte Gruppe</th>
                            <th>Neue Gruppe</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $groups = $groups->sortBy('name'); @endphp
                        @foreach($groups as $group)
                            <tr>
                                <td>{{ $group->name }}</td>
                                <td>
                                    <select name="group_mapping[{{ $group->id }}]" class="form-control">
                                        <option value="">-- Gruppe entfernen --</option>
                                        @foreach($groups as $targetGroup)
                                            <option value="{{ $targetGroup->id }}" @if($group->id == $targetGroup->id) selected @endif>{{ $targetGroup->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <h3>Rollen-Zuordnung</h3>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Alte Rolle</th>
                            <th>Neue Rolle</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td>{{ $role->name }}</td>
                                <td>
                                    <select name="role_mapping[{{ $role->id }}]" class="form-control">
                                        @foreach($roles as $targetRole)
                                            <option value="{{ $targetRole->id }}" @if($role->id == $targetRole->id) selected @endif>{{ $targetRole->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Sind Sie sicher, dass Sie den Schuljahreswechsel starten möchten? Dieser Vorgang kann nicht rückgängig gemacht werden.')">
                        Schuljahreswechsel jetzt starten
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


