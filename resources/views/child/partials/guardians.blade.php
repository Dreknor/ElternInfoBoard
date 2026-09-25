{{-- Bezugspersonen eines Kindes (Konzept kind-zentriertes Familienmodell §10, E5/E6) --}}
@php
    $relationOptions = \App\Enums\GuardianRelation::options();
    $guardianList = $child->parents()->orderBy('name')->get();
@endphp
<div class="card mt-3" id="bezugspersonen">
    <div class="card-header">
        <h5 class="mb-0">Bezugspersonen</h5>
        <small class="text-muted">
            Rechte: <strong>S</strong> = sorgeberechtigt (Rückmeldungen pro Kind),
            <strong>I</strong> = erhält Informationen (Gruppen der Klasse),
            <strong>V</strong> = darf krankmelden &amp; Betreuung verwalten.
        </small>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
            <tr>
                <th>Person</th>
                <th>Beziehung</th>
                <th class="text-center">S</th>
                <th class="text-center">I</th>
                <th class="text-center">V</th>
                <th>gültig bis</th>
                <th>Herkunft</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($guardianList as $guardian)
                @php $pivot = $guardian->pivot; @endphp
                <tr @class(['table-warning' => $pivot->isPendingReview()])>
                    <form action="{{ route('guardians.update', [$child, $guardian]) }}" method="POST" id="guardian-form-{{ $guardian->id }}">
                        @csrf
                        @method('PUT')
                    </form>
                    <td>
                        <a href="{{ url('users/'.$guardian->id) }}">{{ $guardian->name }}</a>
                        @if($guardian->family)
                            <br><small class="text-muted">{{ $guardian->family->name }}</small>
                        @endif
                    </td>
                    <td>
                        <select name="relation" class="custom-select custom-select-sm" form="guardian-form-{{ $guardian->id }}">
                            @foreach($relationOptions as $value => $label)
                                <option value="{{ $value }}" @selected($pivot->relation === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center"><input type="checkbox" name="has_custody" value="1" form="guardian-form-{{ $guardian->id }}" @checked($pivot->has_custody)></td>
                    <td class="text-center"><input type="checkbox" name="receives_information" value="1" form="guardian-form-{{ $guardian->id }}" @checked($pivot->receives_information)></td>
                    <td class="text-center"><input type="checkbox" name="can_manage" value="1" form="guardian-form-{{ $guardian->id }}" @checked($pivot->can_manage)></td>
                    <td><input type="date" name="valid_until" class="form-control form-control-sm" form="guardian-form-{{ $guardian->id }}" value="{{ $pivot->valid_until?->format('Y-m-d') }}"></td>
                    <td>
                        <small>{{ $pivot->sourceLabel() }}</small>
                        @if($pivot->isPendingReview())
                            <br><span class="badge badge-warning">ungeprüft</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        <button type="submit" form="guardian-form-{{ $guardian->id }}" class="btn btn-sm btn-success" title="Speichern"><i class="fas fa-save"></i></button>
                        <form action="{{ route('guardians.defaults', [$child, $guardian]) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary" title="Standardrechte der Beziehungsart übernehmen"><i class="fas fa-undo"></i></button>
                        </form>
                        @if($pivot->isPendingReview())
                            <form action="{{ route('guardians.review', [$child, $guardian]) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-success" title="Als geprüft markieren"><i class="fas fa-check"></i></button>
                            </form>
                        @endif
                        <form action="{{ route('guardians.destroy', [$child, $guardian]) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Beziehung zu {{ addslashes($guardian->name) }} entfernen?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Entfernen"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted">Keine Bezugspersonen hinterlegt.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <form action="{{ route('guardians.store', $child) }}" method="POST" class="form-inline">
            @csrf
            <select name="user_id" class="custom-select mr-2 mb-2" required>
                <option value="">Person wählen …</option>
                @foreach(($guardianCandidates ?? collect()) as $candidate)
                    @unless($guardianList->contains('id', $candidate->id))
                        <option value="{{ $candidate->id }}">{{ $candidate->name }} ({{ $candidate->email }})</option>
                    @endunless
                @endforeach
            </select>
            <select name="relation" class="custom-select mr-2 mb-2">
                @foreach($relationOptions as $value => $label)
                    <option value="{{ $value }}" @selected($value === 'legal_guardian')>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary mb-2">Bezugsperson hinzufügen</button>
        </form>
        <small class="text-muted">Neue Beziehungen erhalten die Standardrechte der Beziehungsart.</small>
    </div>
</div>
