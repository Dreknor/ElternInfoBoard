{{-- Zeile für eine verspätete Abholung (innerhalb einer .ea-table). Erwartet: $entry (LatePickup), $canManageLatePickups, optional $showDate --}}
<tr>
    @if($showDate ?? false)
        <td class="px-3 py-2 whitespace-nowrap text-sm" style="color: var(--color-text-secondary)">{{ $entry->date->format('d.m.Y') }}</td>
    @else
        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium">
            {{ $entry->child->last_name }}, {{ $entry->child->first_name }}
            @if($entry->child->group || $entry->child->class)
                <span class="block text-xs ea-muted">
                    {{ $entry->child->group?->name }}{{ $entry->child->group && $entry->child->class ? ' / ' : '' }}{{ $entry->child->class?->name }}
                </span>
            @endif
        </td>
    @endif
    <td class="px-3 py-2 whitespace-nowrap text-sm" style="color: var(--color-text-secondary)">{{ $entry->expected_time?->format('H:i') }} Uhr</td>
    <td class="px-3 py-2 whitespace-nowrap text-sm" style="color: var(--color-text-secondary)">{{ $entry->picked_up_at?->format('H:i') }} Uhr</td>
    <td class="px-3 py-2 whitespace-nowrap text-sm">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
            +{{ $entry->delay_minutes }} Min.
        </span>
    </td>
    <td class="px-3 py-2 whitespace-nowrap text-sm">
        @if($entry->status === \App\Model\LatePickup::STATUS_OFFEN)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                <i class="fas fa-hourglass-half mr-1"></i> Offen
            </span>
        @elseif($entry->status === \App\Model\LatePickup::STATUS_BESTAETIGT)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300"
                  title="Bestätigt von {{ $entry->reviewer?->name }} am {{ $entry->reviewed_at?->format('d.m.Y H:i') }}">
                <i class="fas fa-check mr-1"></i> Bestätigt
            </span>
        @else
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ea-subtle ea-muted border ea-border"
                  title="Verworfen von {{ $entry->reviewer?->name }} am {{ $entry->reviewed_at?->format('d.m.Y H:i') }}">
                <i class="fas fa-times mr-1"></i> Verworfen
            </span>
        @endif
    </td>
    @if($canManageLatePickups)
        <td class="px-3 py-2 whitespace-nowrap text-right">
            @if($entry->status === \App\Model\LatePickup::STATUS_OFFEN)
                <div class="flex items-center justify-end gap-2">
                    <form action="{{ route('latePickups.confirm', ['latePickup' => $entry->id]) }}" method="post">
                        @csrf
                        <button type="submit"
                                class="ea-btn ea-btn-danger ea-btn-sm"
                                title="Als verspätet bestätigen">
                            <i class="fa fa-check"></i> Bestätigen
                        </button>
                    </form>
                    <form action="{{ route('latePickups.reject', ['latePickup' => $entry->id]) }}" method="post">
                        @csrf
                        <button type="submit"
                                class="ea-btn ea-btn-secondary ea-btn-sm"
                                title="Verwerfen (z.B. Austragen vergessen)">
                            <i class="fa fa-times"></i> Verwerfen
                        </button>
                    </form>
                </div>
            @else
                <span class="text-xs ea-muted">{{ $entry->review_comment }}</span>
            @endif
        </td>
    @endif
</tr>
