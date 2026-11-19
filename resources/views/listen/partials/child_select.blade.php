{{-- Kind-Auswahl bei Listen „je Kind“ (bei genau einem Kind wählt der Server automatisch).
     $bookableChildren darf bereits auf Kinder ohne Buchung eingeschränkt sein. --}}
@php
    $alleKinder = app(\App\Services\App\ListenService::class)->bookableChildren(auth()->user(), $liste);
    $kinderAuswahl = $bookableChildren ?? $alleKinder;
    $selectId = 'child-'.($selectSuffix ?? uniqid());
@endphp
@if($liste->bookingPerChild() && $alleKinder->count() > 1)
    <div class="{{ $wrapperClass ?? '' }}">
        <label for="{{ $selectId }}" class="block text-sm font-semibold mb-1" style="color: var(--color-text-primary);">Für welches Kind?</label>
        <select name="child_id" id="{{ $selectId }}" required
                class="w-full px-3 py-2 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-400"
                style="border: 1px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);">
            @if($kinderAuswahl->count() !== 1)
                <option value="">Bitte wählen …</option>
            @endif
            @foreach($kinderAuswahl as $bookableChild)
                <option value="{{ $bookableChild->id }}" @selected($kinderAuswahl->count() === 1)>{{ $bookableChild->first_name }}</option>
            @endforeach
        </select>
    </div>
@endif
