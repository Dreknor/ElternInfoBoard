{{-- Ein Formularfeld der Elternansicht. Erwartet: $field, $value, $existingFile (bei Korrektur), $childOptions (id => Name) --}}
@php
    $type = $field['type'];
    $key = $field['key'];
    $name = 'answers['.$key.']';
    $id = 'f_'.$key;
    $required = $field['required'] ?? false;
    $helpId = $id.'_hilfe';
    $errorKey = 'answers.'.$key;
    $inputClass = 'block w-full rounded-lg border px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-500 '
        .($errors->has($errorKey) ? 'border-red-500' : 'border-gray-300');
    $describedBy = trim((filled($field['help'] ?? null) ? $helpId : '').' '.($errors->has($errorKey) ? $id.'_fehler' : ''));
@endphp

@if($type === 'info')
    <div class="pt-2" data-field data-type="info">
        <h3 class="text-lg font-semibold text-gray-800">{{ $field['label'] }}</h3>
        @if(filled($field['help'] ?? null))
            <p class="text-sm text-gray-600 mt-1">{!! nl2br(e($field['help'])) !!}</p>
        @endif
    </div>
@else
<div class="bg-white rounded-xl border border-gray-200 p-4" data-field data-type="{{ $type }}" data-label="{{ $field['label'] }}">
    @if(in_array($type, ['yes_no', 'consent', 'radio', 'checkbox', 'child'], true))
        <fieldset @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>
            <legend class="font-medium text-gray-800 mb-1">
                {{ $field['label'] }}
                @if($required)<span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span>@endif
            </legend>
            @if(filled($field['help'] ?? null))
                <p id="{{ $helpId }}" class="text-sm text-gray-600 mb-2">{!! nl2br(e($field['help'])) !!}</p>
            @endif

            @if($type === 'consent')
                <div class="text-sm text-gray-700 bg-gray-50 border border-gray-200 rounded-lg p-3 mb-3 max-h-64 overflow-y-auto whitespace-pre-line" tabindex="0" aria-label="Einwilligungstext">{{ $field['consent_text'] }}</div>
                <p class="text-xs text-gray-500 mb-2">Die Einwilligung ist freiwillig. Sie können sie jederzeit mit Wirkung für die Zukunft widerrufen.</p>
            @endif

            @php
                $choices = match ($type) {
                    'yes_no' => ['yes' => 'Ja', 'no' => 'Nein'],
                    'consent' => ['granted' => 'Ich willige ein', 'denied' => 'Ich willige nicht ein'],
                    'child' => $childOptions ?? [],
                    default => array_combine($field['options'], $field['options']),
                };
                $selected = match ($type) {
                    'checkbox' => (array) ($value ?? []),
                    'child' => \App\Services\Elternabfragen\FieldSchema::childId($value),
                    default => $value,
                };
            @endphp
            @if($type === 'child' && $choices === [])
                <p class="text-sm text-gray-600">Für Ihr Konto sind keine Kinder hinterlegt, für die Sie sorgeberechtigt sind.</p>
            @endif
            <div class="space-y-2">
                @foreach($choices as $choiceValue => $choiceLabel)
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 px-3 py-3 cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        @if($type === 'checkbox')
                            <input type="checkbox" name="{{ $name }}[]" value="{{ $choiceValue }}" class="mt-1 h-5 w-5"
                                   @checked(in_array($choiceValue, $selected, true)) data-choice="{{ $choiceLabel }}">
                        @else
                            <input type="radio" name="{{ $name }}" value="{{ $choiceValue }}" class="mt-1 h-5 w-5"
                                   @checked((string) $selected === (string) $choiceValue) @required($required) data-choice="{{ $choiceLabel }}">
                        @endif
                        <span class="text-gray-800">{{ $choiceLabel }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @else
        <label for="{{ $id }}" class="block font-medium text-gray-800 mb-1">
            {{ $field['label'] }}
            @if($required)<span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span>@endif
        </label>
        @if(filled($field['help'] ?? null))
            <p id="{{ $helpId }}" class="text-sm text-gray-600 mb-2">{!! nl2br(e($field['help'])) !!}</p>
        @endif

        @switch($type)
            @case('select')
                <select id="{{ $id }}" name="{{ $name }}" class="{{ $inputClass }}" @required($required) @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                    <option value="">Bitte wählen …</option>
                    @foreach($field['options'] as $option)
                        <option value="{{ $option }}" @selected((string) $value === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                @break
            @case('textarea')
                <textarea id="{{ $id }}" name="{{ $name }}" rows="4" class="{{ $inputClass }}" @required($required)
                          @if($field['min_length'] ?? null) minlength="{{ $field['min_length'] }}" @endif
                          maxlength="{{ $field['max_length'] ?: 5000 }}"
                          @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ is_scalar($value) ? $value : '' }}</textarea>
                @break
            @case('date')
                <input type="date" id="{{ $id }}" name="{{ $name }}" value="{{ is_scalar($value) ? $value : '' }}" class="{{ $inputClass }}" @required($required)
                       @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                @break
            @case('file')
                @if($existingFile)
                    <p class="text-sm text-gray-700 mb-2" data-existing><i class="fas fa-paperclip" aria-hidden="true"></i> Bisherige Datei: {{ $existingFile['name'] }} – wird übernommen, wenn Sie keine neue auswählen.</p>
                @endif
                <input type="file" id="{{ $id }}" name="{{ $name }}" accept=".pdf,.jpg,.jpeg,.png,.heic,application/pdf,image/*"
                       class="block w-full text-base" @required($required && ! $existingFile) @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                <p class="text-xs text-gray-500 mt-1">PDF oder Bild. Dateien werden erst beim Absenden übernommen, nicht beim Zwischenspeichern.</p>
                @break
            @default
                @php
                    $inputType = match ($field['format'] ?? null) {
                        'email' => 'email',
                        'phone' => 'tel',
                        default => 'text',
                    };
                    $inputMode = match ($field['format'] ?? null) {
                        'number', 'postal_code' => 'numeric',
                        default => null,
                    };
                @endphp
                <input type="{{ $inputType }}" id="{{ $id }}" name="{{ $name }}" value="{{ is_scalar($value) ? $value : '' }}" class="{{ $inputClass }}"
                       @if($inputMode) inputmode="{{ $inputMode }}" @endif
                       @if(($field['format'] ?? null) === 'postal_code') pattern="[0-9]{5}" @endif
                       @if($field['min_length'] ?? null) minlength="{{ $field['min_length'] }}" @endif
                       maxlength="{{ $field['max_length'] ?: 255 }}"
                       autocomplete="{{ ($field['format'] ?? null) === 'email' ? 'email' : (($field['format'] ?? null) === 'phone' ? 'tel' : 'off') }}"
                       @required($required) @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                       @if($field['error_message'] ?? null) data-error="{{ $field['error_message'] }}" @endif>
        @endswitch
    @endif

    @error($errorKey)
        <p id="{{ $id }}_fehler" class="text-sm text-red-700 mt-2" role="alert"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $message }}</p>
    @enderror
</div>
@endif
