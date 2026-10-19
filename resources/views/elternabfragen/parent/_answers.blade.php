{{-- Lesbare Zusammenfassung einer Antwortversion. Erwartet: $fields, $answers, $schema, optional $fileRoute (Closure key => url) --}}
<dl class="divide-y divide-gray-100">
    @foreach($fields as $field)
        @continue($field['type'] === 'info')
        <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-gray-600">{{ $field['label'] }}</dt>
            <dd class="text-sm text-gray-900 sm:col-span-2 mt-1 sm:mt-0 break-words whitespace-pre-line">
                @php $value = $answers[$field['key']] ?? null; @endphp
                @if($field['type'] === 'file' && is_array($value) && isset($fileRoute))
                    <a href="{{ $fileRoute($field['key']) }}" class="text-blue-700 underline"><i class="fas fa-paperclip" aria-hidden="true"></i> {{ $value['name'] ?? 'Datei' }}</a>
                @else
                    {{ $schema->display($field, $value) }}
                @endif
            </dd>
        </div>
    @endforeach
</dl>
