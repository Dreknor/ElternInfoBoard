@php
    $account = $child->studentAccount;
    $klasse = $child->class?->name ?? $child->group?->name ?? '–';
    $statusLabel = ! $account ? 'kein Konto' : ($account->is_active ? 'aktiv' : (($settings->eltern_zustimmung && $account->student_consent !== 'ja') ? 'wartet auf Zustimmung' : 'gesperrt'));
    $statusClass = ! $account ? 'bg-gray-100 text-gray-700' : ($account->is_active ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800');
    $loginLabel = ! $account ? '' : ($account->ucs_oidc_sub || $account->ucs_source === 'kelvin' ? 'SSO' : 'Benutzername');
@endphp

@php ob_start(); @endphp
<div class="flex flex-wrap justify-end gap-1">
    @if(! $account)
        <form method="POST" action="{{ route('schuelerkonten.store') }}">
            @csrf
            <input type="hidden" name="child_id" value="{{ $child->id }}">
            <button class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Konto anlegen</button>
        </form>
    @else
        <form method="POST" action="{{ route('schuelerkonten.codes') }}" onsubmit="return confirm('Neuen Code erzeugen? Ein alter Code wird ungültig.')">
            @csrf
            <input type="hidden" name="student_ids[]" value="{{ $account->id }}">
            <button class="btn btn-outline-primary btn-sm" title="Zugangszettel mit neuem Code"><i class="fas fa-print"></i> Code</button>
        </form>
        <details class="relative">
            <summary class="btn btn-outline-secondary btn-sm list-none" title="Benutzername ändern"><i class="fas fa-pen"></i></summary>
            <form method="POST" action="{{ route('schuelerkonten.update', $account->id) }}" class="absolute right-0 z-20 mt-1 flex w-64 gap-1 rounded-lg border bg-white p-2 shadow-lg" style="border-color: var(--color-card-border); background: var(--color-card-bg);">
                @csrf @method('PUT')
                <input name="username" value="{{ $account->username }}" class="form-control form-control-sm" aria-label="Benutzername" autocapitalize="none">
                <button class="btn btn-primary btn-sm">OK</button>
            </form>
        </details>
        @if($account->is_active)
            <form method="POST" action="{{ route('schuelerkonten.deactivate', $account->id) }}">
                @csrf
                <button class="btn btn-outline-warning btn-sm" title="Sperren"><i class="fas fa-lock"></i></button>
            </form>
        @else
            <form method="POST" action="{{ route('schuelerkonten.activate', $account->id) }}">
                @csrf
                <button class="btn btn-outline-success btn-sm" title="Entsperren"><i class="fas fa-lock-open"></i></button>
            </form>
        @endif
        <form method="POST" action="{{ route('schuelerkonten.destroy', $account->id) }}" onsubmit="return confirm('Konto endgültig löschen? Das Kind bleibt erhalten.')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm" title="Löschen"><i class="fas fa-trash"></i></button>
        </form>
    @endif
</div>
@php $actions = ob_get_clean(); @endphp

@if($layout === 'row')
    <tr>
        <td>@if($account)<input type="checkbox" value="{{ $account->id }}" x-model.number="selected" aria-label="Auswählen">@endif</td>
        <td class="font-medium">{{ $child->last_name }}, {{ $child->first_name }}</td>
        <td>{{ $klasse }}</td>
        <td class="font-mono text-sm">{{ $account?->username ?? '–' }}</td>
        <td><span class="rounded-full px-2 py-0.5 text-xs {{ $statusClass }}">{{ $statusLabel }}</span></td>
        <td class="hidden text-sm lg:table-cell">{{ $loginLabel }}@if($account?->last_online_at)<div class="text-xs" style="color: var(--color-text-secondary);">zuletzt {{ $account->last_online_at->format('d.m.Y') }}</div>@endif</td>
        <td>{!! $actions !!}</td>
    </tr>
@else
    <div class="rounded-lg border p-3" style="border-color: var(--color-card-border);">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="font-medium">{{ $child->last_name }}, {{ $child->first_name }}</div>
                <div class="text-sm" style="color: var(--color-text-secondary);">{{ $klasse }}@if($account) · <span class="font-mono break-all">{{ $account->username }}</span>@endif</div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span class="rounded-full px-2 py-0.5 text-xs {{ $statusClass }}">{{ $statusLabel }}</span>
                @if($account)<input type="checkbox" value="{{ $account->id }}" x-model.number="selected" aria-label="Auswählen">@endif
            </div>
        </div>
        <div class="mt-2">{!! $actions !!}</div>
    </div>
@endif
