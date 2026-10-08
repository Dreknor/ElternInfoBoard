{{-- Kind-Auswahl bei Listen „je Kind“ (bei genau einem Kind wählt der Server automatisch) --}}
@php
    $bookableChildren ??= app(\App\Services\App\ListenService::class)->bookableChildren(auth()->user(), $liste);
@endphp
@if($liste->bookingPerChild() && $bookableChildren->count() > 1)
    <select name="child_id" required
            class="px-3 py-2 border-2 border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none"
            title="Für welches Kind?">
        <option value="">Für welches Kind?</option>
        @foreach($bookableChildren as $bookableChild)
            <option value="{{ $bookableChild->id }}">{{ $bookableChild->first_name }}</option>
        @endforeach
    </select>
@endif
