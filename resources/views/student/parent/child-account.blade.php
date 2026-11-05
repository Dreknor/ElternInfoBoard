@php
    $studentSettings = app(\App\Settings\SchuelerSetting::class);
    $account = $child->studentAccount;
    $resolver = app(\App\Services\Family\FamilyResolver::class);
    $mayConsent = $account && $studentSettings->eltern_zustimmung && $resolver->hasAccessToChild(auth()->user(), $child, \App\Enums\GuardianRight::Custody);
    $mayCode = $account && $studentSettings->eltern_codes && $studentSettings->login_benutzername && $account->is_active && $account->ucs_source !== 'kelvin' && $resolver->hasAccessToChild(auth()->user(), $child, \App\Enums\GuardianRight::Manage);
    $issued = session('student_code.child') === $child->id ? session('student_code') : null;
@endphp
<div class="text-xs mt-2 pt-2 border-t" style="border-color: var(--color-card-border); color: var(--color-text-secondary);">
    <div class="font-medium mb-0.5" style="color: var(--color-text-primary);"><i class="fas fa-user-graduate mr-1"></i>Schülerkonto</div>
    @if(! $account)
        <div>nicht vorhanden</div>
    @else
        <div>
            Benutzername <span class="font-mono break-all">{{ $account->username }}</span> –
            @if($account->is_active)
                <span class="text-green-700">aktiv</span>
            @elseif($studentSettings->eltern_zustimmung && $account->student_consent !== 'ja')
                <span class="text-amber-700">wartet auf Zustimmung</span>
            @else
                <span class="text-amber-700">gesperrt</span>
            @endif
        </div>
        @if($studentSettings->eltern_zustimmung && $account->student_consent_at)
            <div style="color: var(--color-text-muted);">Letzte Entscheidung: {{ $account->student_consent === 'ja' ? 'zugestimmt' : 'widersprochen' }} am {{ $account->student_consent_at->format('d.m.Y') }}</div>
        @endif
        <div class="mt-1 flex flex-wrap gap-2">
            @if($mayConsent)
                @if($account->student_consent !== 'ja')
                    <form method="POST" action="{{ route('kinder.schuelerkonto.consent', $child->id) }}">
                        @csrf <input type="hidden" name="zustimmung" value="1">
                        <button class="rounded border border-green-600 px-2 py-1 text-green-700 hover:bg-green-50"><i class="fas fa-check mr-1"></i>Zustimmen</button>
                    </form>
                @endif
                @if($account->student_consent !== 'nein')
                    <form method="POST" action="{{ route('kinder.schuelerkonto.consent', $child->id) }}" onsubmit="return confirm('Widersprechen? Das Konto wird sofort gesperrt.')">
                        @csrf <input type="hidden" name="zustimmung" value="0">
                        <button class="rounded border border-red-600 px-2 py-1 text-red-700 hover:bg-red-50"><i class="fas fa-ban mr-1"></i>Widersprechen</button>
                    </form>
                @endif
            @endif
            @if($mayCode)
                <form method="POST" action="{{ route('kinder.schuelerkonto.code', $child->id) }}" onsubmit="return confirm('Neuen Code erzeugen? Ein alter Code wird ungültig.')">
                    @csrf
                    <button class="rounded border px-2 py-1 hover:bg-black/5" style="border-color: var(--color-card-border);"><i class="fas fa-key mr-1"></i>Neuen Code erzeugen</button>
                </form>
            @endif
        </div>
        @if($issued)
            <div class="mt-2 rounded border border-blue-300 bg-blue-50 p-2 text-blue-900">
                <div class="flex flex-wrap items-center gap-3">
                    <img src="{{ $issued['qr'] }}" alt="QR-Code zum Einlösen" class="h-28 w-28 bg-white">
                    <div>
                        <div>Code: <span class="font-mono text-sm font-bold">{{ $issued['code'] }}</span></div>
                        <div>gültig bis {{ $issued['valid_until'] }}</div>
                        <div class="mt-1">Ihr Kind scannt den QR-Code oder gibt den Code unter „Code einlösen“ ein. Der Code wird nur jetzt angezeigt.</div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
