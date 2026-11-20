{{--
    Ansicht der Anwesenheitsliste für dieses Gerät wählen (Cookie, siehe CareController::setDeviceView).
    „Standard“ folgt der Einstellung unter Einstellungen → Hort.
    Parameter: $selectClass (optional) – Klassen für das Auswahlfeld.
--}}
@php
    $viewLabels = ['simple' => 'Einfache Liste', 'detailed' => 'Detailansicht', 'board' => 'Kachelansicht'];
    $deviceView = $careSettings->deviceViewMode();
@endphp
<label class="att-view-switch inline-flex items-center gap-1.5 text-xs font-semibold whitespace-nowrap mb-0"
       title="Ansicht nur für dieses Gerät">
    <i class="fas fa-desktop" aria-hidden="true"></i>
    <span class="sr-only">Ansicht für dieses Gerät</span>
    <select class="{{ $selectClass ?? 'rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 shadow-sm' }}"
            onchange="window.location.href = this.value">
        <option value="{{ route('anwesenheit.view', 'standard') }}" @selected($deviceView === null)>
            Standard ({{ $viewLabels[$careSettings->configuredViewMode()] }})
        </option>
        @foreach($viewLabels as $value => $label)
            <option value="{{ route('anwesenheit.view', $value) }}" @selected($deviceView === $value)>{{ $label }}</option>
        @endforeach
    </select>
</label>
