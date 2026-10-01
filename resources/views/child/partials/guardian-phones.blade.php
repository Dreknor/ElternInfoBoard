@php
    /** @var \App\Model\Child $child */
    $custodians = $child->parents->filter(function ($guardian) {
        $validUntil = $guardian->pivot->valid_until;

        return $guardian->pivot->has_custody
            && ($validUntil === null || $validUntil->isToday() || $validUntil->isFuture());
    });
@endphp

<div class="card mt-3">
    <div class="card-header">
        <h5 class="mb-0">Nichtöffentliche Telefonnummern der Sorgeberechtigten</h5>
        <small class="text-muted">Nur für Mitarbeitende und Betreuung sichtbar, nicht für andere Eltern.</small>
    </div>
    <div class="card-body">
        @forelse($custodians as $guardian)
            <form action="{{ route('child.guardian.phone', [$child, $guardian]) }}" method="POST" class="row align-items-end mb-3">
                @csrf
                @method('PUT')
                <div class="col-md-5 mb-2">
                    <label for="guardian-phone-{{ $guardian->id }}" class="form-label mb-1">{{ $guardian->name }}</label>
                    <input type="tel" class="form-control" id="guardian-phone-{{ $guardian->id }}" name="phone"
                           value="{{ old('phone', $guardian->phone) }}" maxlength="50" autocomplete="off">
                </div>
                <div class="col-auto mb-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Telefonnummer speichern
                    </button>
                </div>
            </form>
        @empty
            <p class="text-muted mb-0">Keine aktuell Sorgeberechtigten hinterlegt.</p>
        @endforelse
    </div>
</div>
