@extends('layouts.student')
@section('title', '- Mein Zugang')
@section('content')
    <h1 class="mb-4 text-2xl font-bold">Mein Zugang</h1>
    <div class="grid gap-4 md:grid-cols-2">
        <section class="student-card rounded-xl p-4">
            <dl class="space-y-2 text-sm">
                <div><dt class="student-muted">Name</dt><dd class="font-medium">{{ $student->name }}</dd></div>
                @if($klasse)<div><dt class="student-muted">Klasse</dt><dd class="font-medium">{{ $klasse }}</dd></div>@endif
                @if($student->username)<div><dt class="student-muted">Benutzername</dt><dd class="break-all font-mono">{{ $student->username }}</dd></div>@endif
            </dl>
            <p class="mt-4 text-xs student-muted">Änderungen an Name oder Klasse macht das Sekretariat.</p>
        </section>

        @if($canChangePassword)
            <section class="student-card rounded-xl p-4">
                <h2 class="mb-3 font-semibold">Passwort ändern</h2>
                <form method="POST" action="{{ route('student.profile.password') }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    @foreach(['current_password' => 'Bisheriges Passwort', 'password' => 'Neues Passwort (mind. 8 Zeichen)', 'password_confirmation' => 'Neues Passwort wiederholen'] as $field => $label)
                        <div>
                            <label for="{{ $field }}" class="mb-1 block text-sm">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="password" required autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}"
                                   class="w-full rounded-lg border px-3 py-2" style="background: var(--color-input-bg, #fff); border-color: var(--color-input-border, #cbd5e1);">
                        </div>
                    @endforeach
                    <button type="submit" class="w-full rounded-lg px-4 py-2 font-medium text-white sm:w-auto" style="background: var(--color-primary);">Speichern</button>
                </form>
            </section>
        @endif
    </div>
@endsection
