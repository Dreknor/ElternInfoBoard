{{-- Liste der aktuell (heute) krankgemeldeten Kinder. Wird sowohl beim
     initialen Seitenaufruf als auch nach jeder Express-Meldung per AJAX
     neu geladen und ausgetauscht. --}}
@if($krankmeldungen && $krankmeldungen->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b-2" style="background-color: var(--color-surface-subtle); border-color: var(--color-card-border)">
                    <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-text-primary)">
                        <i class="fas fa-child mr-2" style="color: var(--color-text-secondary)"></i>Kind
                    </th>
                    <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-text-primary)">
                        <i class="fas fa-calendar-alt mr-2" style="color: var(--color-text-secondary)"></i>Zeitraum
                    </th>
                    <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-text-primary)">
                        <i class="fas fa-info-circle mr-2" style="color: var(--color-text-secondary)"></i>Informationen
                    </th>
                </tr>
            </thead>
            <tbody class="divide-theme">
                @foreach($krankmeldungen as $krankmeldung)
                    <tr class="transition-colors duration-200"
                        onmouseover="this.style.backgroundColor='var(--color-surface-subtle)'"
                        onmouseout="this.style.backgroundColor=''">
                        <td class="px-4 py-3 align-top">
                            <span class="inline-flex items-center gap-2 px-3 py-1 text-sm font-medium rounded-full"
                                  style="background-color: var(--color-widget-body-bg); color: var(--color-widget-primary-border); border: 1px solid var(--color-widget-primary-border)">
                                <i class="fas fa-child"></i>
                                {{ $krankmeldung->name }}
                            </span>
                            @php
                                $label = collect([$krankmeldung->child?->group?->name, $krankmeldung->child?->class?->name])->filter()->implode(' / ');
                            @endphp
                            @if($label)
                                <div class="text-xs mt-1" style="color: var(--color-text-muted)">{{ $label }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top text-sm" style="color: var(--color-text-secondary)">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-arrow-right text-xs" style="color: var(--color-text-muted)"></i>
                                <span class="font-medium">{{ $krankmeldung->start->format('d.m.Y') }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <i class="fas fa-arrow-left text-xs" style="color: var(--color-text-muted)"></i>
                                <span class="font-medium">{{ $krankmeldung->ende->format('d.m.Y') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="space-y-2">
                                @if($krankmeldung->kommentar)
                                    <div class="hidden md:block text-sm p-2 rounded" style="color: var(--color-text-primary); background-color: var(--color-surface-subtle)">
                                        {{ $krankmeldung->kommentar }}
                                    </div>
                                @endif
                                <div class="text-xs border-t pt-2" style="color: var(--color-text-muted); border-color: var(--color-card-border)">
                                    <p class="flex items-center gap-1 mb-1">
                                        <i class="fas fa-clock"></i>
                                        {{ $krankmeldung->created_at->format('d.m.Y H:i') }} Uhr
                                    </p>
                                    <p class="flex items-center gap-1 mb-0">
                                        <i class="fas fa-user-check"></i>
                                        von {{ $krankmeldung->user?->name }}
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="flex items-start gap-3 p-4 border-l-4 rounded-lg"
         style="background-color: var(--color-widget-body-bg); border-color: var(--color-widget-warning-from)">
        <i class="fas fa-info-circle mt-0.5 flex-shrink-0" style="color: var(--color-widget-warning-from)"></i>
        <p class="text-sm mb-0" style="color: var(--color-widget-warning-border)">
            Aktuell sind keine Kinder krankgemeldet.
        </p>
    </div>
@endif
