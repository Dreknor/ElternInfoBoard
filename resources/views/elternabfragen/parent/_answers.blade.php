{{-- Lesbare Zusammenfassung einer Antwortversion. Erwartet: $fields, $answers, $schema, optional $fileRoute (Closure key => url) --}}
<dl class="ea-divide mb-0">
    @foreach($fields as $field)
        @continue($field['type'] === 'info')
        @php $value = $answers[$field['key']] ?? null; @endphp
        <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium ea-muted">{{ $field['label'] }}</dt>
            @if($field['type'] === 'file' && is_array($value) && isset($fileRoute))
                <dd class="text-sm ea-text sm:col-span-2 mt-1 sm:mt-0 mb-0 break-words"><a href="{{ $fileRoute($field['key']) }}" class="ea-link"><i class="fas fa-paperclip" aria-hidden="true"></i> {{ $value['name'] ?? 'Datei' }}</a></dd>
            @else
                {{-- whitespace-pre-line: Inhalt ohne umgebende Leerzeichen ausgeben --}}
                <dd class="text-sm font-medium ea-text sm:col-span-2 mt-1 sm:mt-0 mb-0 break-words whitespace-pre-line">{{ $schema->display($field, $value) }}</dd>
            @endif
        </div>
    @endforeach
</dl>
