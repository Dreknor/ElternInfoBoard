{{-- Wer muss antworten? (Konzept §6.5, E2/E7) --}}
@php $scopeValue = old('scope', $scope ?? 'child'); @endphp
<div class="form-group">
    <label>Wer soll antworten?</label>
    <select class="custom-select" name="scope">
        <option value="child" @selected($scopeValue === 'child')>Pro Kind in den Gruppen der Nachricht (nur Sorgeberechtigte)</option>
        <option value="family" @selected($scopeValue === 'family')>Eine Antwort pro Familie</option>
        <option value="person" @selected($scopeValue === 'person')>Jede Person einzeln</option>
    </select>
    <small class="form-text text-muted">
        Bei „pro Kind“ antwortet für jedes Kind ein sorgeberechtigtes Elternteil. Empfänger ohne Kind in den Gruppen
        (z. B. Elternrat, Personal) antworten einmal pro Familie.
    </small>
</div>
