@extends('layouts.app')

@section('title', '| Elternabfragen · '.$version->title)

@section('content')
@php
    $template = $version->template;
    $status = $version->status;
    $fourEyesBlocked = app(\App\Settings\ElternabfragenSetting::class)->four_eyes && (int) $version->created_by === auth()->id();
    $fieldTypes = \App\Enums\InquiryFieldType::options();
    $fieldIcons = [
        'consent' => 'fas fa-file-signature', 'yes_no' => 'fas fa-toggle-on', 'checkbox' => 'far fa-check-square', 'radio' => 'far fa-dot-circle',
        'select' => 'fas fa-caret-square-down', 'text' => 'fas fa-font', 'textarea' => 'fas fa-align-left', 'date' => 'far fa-calendar',
        'file' => 'fas fa-paperclip', 'child' => 'fas fa-child', 'info' => 'fas fa-heading',
    ];
@endphp
<div class="container-fluid px-4 py-6 max-w-6xl">
    @include('elternabfragen.admin._nav', [
        'title' => $version->title,
        'subtitle' => 'Vorlage · Version '.$version->version.($template->category ? ' · '.$template->category : ''),
        'icon' => 'fas fa-file-signature',
        'back' => ['url' => route('elternabfragen.admin.templates.index'), 'label' => 'Alle Vorlagen'],
    ])

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6 min-w-0">
            {{-- Prüfung und Freigabe --}}
            @if($status->value === 'in_review')
                @can('approve elternabfragen')
                    <section id="pruefung" class="ea-card scroll-mt-4" style="border: 2px solid #f59e0b">
                        <div class="ea-card-header">
                            <div class="ea-card-icon !bg-amber-100 !text-amber-700"><i class="fas fa-user-check" aria-hidden="true"></i></div>
                            <div>
                                <h2 class="ea-card-title">Prüfung und Freigabe</h2>
                                <p class="ea-card-sub">Erst freigegebene Vorlagen können für Abfragen verwendet werden.</p>
                            </div>
                        </div>
                        <div class="ea-card-body">
                            @if($fourEyesBlocked)
                                <div class="ea-alert ea-alert-warning"><i class="fas fa-user-lock" aria-hidden="true"></i>
                                    <p class="mb-0">
                                        Vier-Augen-Prinzip: Sie haben diese Version erstellt, daher muss eine andere Person mit Freigaberecht sie prüfen.
                                        @can('edit settings')
                                            Das Vier-Augen-Prinzip lässt sich in den <a href="{{ route('elternabfragen.admin.settings') }}" class="underline">Einstellungen</a> abschalten.
                                        @endcan
                                    </p>
                                </div>
                            @else
                                <div class="grid gap-6 lg:grid-cols-2">
                                    <form method="POST" action="{{ route('elternabfragen.admin.templates.approve', $version) }}" class="space-y-3">
                                        @csrf
                                        <p class="text-sm ea-muted mb-0">
                                            Zweck, Pflichtfelder, Antwortoptionen und Verständlichkeit prüfen – am besten in der
                                            <a href="{{ route('elternabfragen.admin.templates.preview', $version) }}" target="_blank" class="ea-link">Elternvorschau</a>.
                                        </p>
                                        @if($version->is_sensitive)
                                            <label class="ea-toggle-row text-sm ea-text"><input type="checkbox" name="privacy_checked" value="1" class="ea-check mt-0.5" required> <span>Datenschutzrechtliche bzw. schulinterne Zusatzprüfung ist erfolgt.</span></label>
                                        @endif
                                        <textarea name="note" rows="2" class="ea-input" placeholder="Prüfvermerk (optional)"></textarea>
                                        <button class="ea-btn ea-btn-success w-full"><i class="fas fa-check-double" aria-hidden="true"></i> Freigeben</button>
                                    </form>
                                    <form method="POST" action="{{ route('elternabfragen.admin.templates.reject', $version) }}" class="space-y-3 lg:pl-6 lg:border-l ea-border">
                                        @csrf
                                        <textarea name="note" rows="3" required class="ea-input" placeholder="Was soll überarbeitet werden?"></textarea>
                                        <button class="ea-btn ea-btn-danger w-full"><i class="fas fa-undo" aria-hidden="true"></i> Zur Überarbeitung zurückgeben</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </section>
                @endcan
            @endif

            <section class="ea-card">
                <div class="ea-card-header">
                    <div class="flex flex-wrap items-center gap-2 flex-1">
                        <x-elternabfragen.badge :color="$status->color()" dot>{{ $status->label() }}</x-elternabfragen.badge>
                        @if($version->is_sensitive) <x-elternabfragen.badge color="red" icon="fas fa-user-shield">sensible Inhalte</x-elternabfragen.badge> @endif
                        @if($version->requires_all_guardians) <x-elternabfragen.badge color="blue" icon="fas fa-user-friends">Bestätigung aller Sorgeberechtigten</x-elternabfragen.badge> @endif
                        @if($template->isArchived()) <x-elternabfragen.badge color="slate">archiviert</x-elternabfragen.badge> @endif
                    </div>
                    <a href="{{ route('elternabfragen.admin.templates.preview', $version) }}" class="ea-btn ea-btn-secondary ea-btn-sm"><i class="fas fa-eye" aria-hidden="true"></i> Elternvorschau</a>
                </div>
                <div class="ea-card-body space-y-5">
                    @if($version->review_note)
                        <div class="ea-alert ea-alert-neutral"><i class="fas fa-comment-dots" aria-hidden="true"></i><p class="mb-0"><strong>Prüfvermerk:</strong> {{ $version->review_note }}</p></div>
                    @endif
                    <div>
                        <p class="ea-section-title mb-1.5"><i class="fas fa-bullseye" aria-hidden="true"></i> Zweck</p>
                        <p class="text-sm ea-text whitespace-pre-line mb-0">{{ $version->purpose }}</p>
                    </div>
                    @if($version->parent_info)
                        <div>
                            <p class="ea-section-title mb-1.5"><i class="fas fa-info-circle" aria-hidden="true"></i> Elterninformation</p>
                            <p class="text-sm ea-text whitespace-pre-line mb-0">{{ $version->parent_info }}</p>
                        </div>
                    @endif
                    <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-0">
                        @foreach([
                            ['fas fa-building', 'Zuständige Stelle', $version->responsible_office ?? '–'],
                            ['fas fa-users', 'Zielgruppe', $version->target_hint ?? '–'],
                            ['far fa-calendar-alt', 'Standardfrist', $version->default_deadline_days.' Tage'],
                            ['fas fa-archive', 'Aufbewahrung', $version->retention_days ? $version->retention_days.' Tage nach Abschluss' : 'Standard'],
                        ] as [$icon, $term, $value])
                            <div class="rounded-xl p-3 ea-subtle">
                                <dt class="text-xs ea-muted"><i class="{{ $icon }} mr-1" aria-hidden="true"></i> {{ $term }}</dt>
                                <dd class="text-sm font-medium ea-text mb-0 mt-1 break-words">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    @if($version->retention_note)
                        <div>
                            <p class="ea-section-title mb-1.5"><i class="fas fa-trash-alt" aria-hidden="true"></i> Hinweise zu Aufbewahrung und Löschung</p>
                            <p class="text-sm ea-text whitespace-pre-line mb-0">{{ $version->retention_note }}</p>
                        </div>
                    @endif
                </div>
            </section>

            <section class="ea-card">
                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="fas fa-th-list" aria-hidden="true"></i></div>
                    <h2 class="ea-card-title">Formularfelder ({{ count($version->inputFields()) }} Eingaben)</h2>
                </div>
                <ol class="ea-card-body space-y-3 mb-0">
                    @foreach($version->fields as $field)
                        @if($field['type'] === 'info')
                            <li class="pt-2">
                                <p class="text-sm font-bold ea-text mb-0"><i class="fas fa-heading ea-muted mr-1" aria-hidden="true"></i> {{ $field['label'] }}</p>
                                @if($field['help'] ?? null)<p class="text-sm ea-muted mb-0 mt-1">{{ $field['help'] }}</p>@endif
                            </li>
                        @else
                            <li class="rounded-xl p-4 text-sm" style="border: 1px solid var(--color-card-border)">
                                <div class="flex items-start gap-3">
                                    <span class="ea-card-icon !w-8 !h-8"><i class="{{ $fieldIcons[$field['type']] ?? 'fas fa-question' }}" aria-hidden="true"></i></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <strong class="ea-text">{{ $field['label'] }}</strong>
                                            @if($field['required'] ?? false) <x-elternabfragen.badge color="amber">Pflicht</x-elternabfragen.badge> @endif
                                        </div>
                                        <p class="text-xs ea-muted mb-0 mt-0.5">
                                            {{ $fieldTypes[$field['type']] ?? $field['type'] }}
                                            @if($field['format'] ?? null) · Format: {{ \App\Services\Elternabfragen\FieldSchema::FORMATS[$field['format']] ?? $field['format'] }} @endif
                                            @if(($field['min_length'] ?? null) || ($field['max_length'] ?? null)) · {{ $field['min_length'] ?? 0 }}–{{ $field['max_length'] ?? '∞' }} @endif
                                        </p>
                                        @if($field['help'] ?? null)<p class="ea-muted mt-2 mb-0">{{ $field['help'] }}</p>@endif
                                        @if(! empty($field['options']))
                                            <div class="flex flex-wrap gap-1.5 mt-2">
                                                @foreach($field['options'] as $option)<span class="ea-chip !py-0.5 !px-2.5 text-xs">{{ $option }}</span>@endforeach
                                            </div>
                                        @endif
                                        @if($field['consent_text'] ?? null)<p class="ea-text mt-2 mb-0 whitespace-pre-line ea-subtle p-3 rounded-lg">{{ $field['consent_text'] }}</p>@endif
                                        @if($field['error_message'] ?? null)<p class="text-xs ea-muted mt-2 mb-0"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> Fehlermeldung: {{ $field['error_message'] }}</p>@endif
                                    </div>
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="ea-card">
                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="fas fa-bolt" aria-hidden="true"></i></div>
                    <h2 class="ea-card-title">Aktionen</h2>
                </div>
                <div class="ea-card-body space-y-2">
                    @can('manage elternabfragen')
                        @if($status->isEditable())
                            <a href="{{ route('elternabfragen.admin.templates.edit', $version) }}" class="ea-btn ea-btn-primary w-full"><i class="fas fa-pen" aria-hidden="true"></i> Bearbeiten</a>
                            <form method="POST" action="{{ route('elternabfragen.admin.templates.submit', $version) }}">@csrf
                                <button class="ea-btn ea-btn-success w-full"><i class="fas fa-paper-plane" aria-hidden="true"></i> Zur Prüfung einreichen</button>
                            </form>
                        @elseif(in_array($status->value, ['approved', 'superseded'], true) && ! $template->isArchived())
                            @if($status->value === 'approved')
                                <a href="{{ route('elternabfragen.admin.inquiries.create', ['vorlage' => $version->id]) }}" class="ea-btn ea-btn-success w-full"><i class="fas fa-paper-plane" aria-hidden="true"></i> Abfrage aus dieser Vorlage erstellen</a>
                            @endif
                            <form method="POST" action="{{ route('elternabfragen.admin.templates.new-version', $template) }}">@csrf
                                <button class="ea-btn ea-btn-secondary w-full"><i class="fas fa-code-branch" aria-hidden="true"></i> Neue Version anlegen</button>
                            </form>
                        @endif
                        @unless($template->isArchived())
                            <form method="POST" action="{{ route('elternabfragen.admin.templates.archive', $template) }}" onsubmit="return confirm('Vorlage archivieren?')">@csrf
                                <button class="ea-btn ea-btn-ghost w-full"><i class="fas fa-archive" aria-hidden="true"></i> Archivieren</button>
                            </form>
                        @endunless
                    @endcan

                    @if($status->value === 'in_review')
                        @can('approve elternabfragen')
                            <a href="#pruefung" class="ea-btn ea-btn-warning w-full"><i class="fas fa-user-check" aria-hidden="true"></i> Zur Prüfung</a>
                        @endcan
                    @endif
                </div>
            </section>

            <section class="ea-card">
                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="fas fa-code-branch" aria-hidden="true"></i></div>
                    <h2 class="ea-card-title">Versionen</h2>
                </div>
                <ul class="ea-divide mb-0 text-sm">
                    @foreach($template->versions as $v)
                        <li class="px-5 py-3 flex items-center justify-between gap-2 {{ $v->id === $version->id ? 'ea-subtle' : '' }}">
                            <a href="{{ route('elternabfragen.admin.templates.show', $v) }}" class="{{ $v->id === $version->id ? 'font-bold ea-text' : 'ea-link' }}">Version {{ $v->version }}</a>
                            <x-elternabfragen.badge :color="$v->status->color()" dot>{{ $v->status->label() }}</x-elternabfragen.badge>
                        </li>
                    @endforeach
                </ul>
            </section>

            @include('elternabfragen.admin._protocol', ['events' => $events])
        </aside>
    </div>
</div>
@endsection

@if(session('templateDraftSaved'))
    @push('js')
    <script>
        try { localStorage.removeItem(@js(session('templateDraftSaved'))); } catch (e) {}
    </script>
    @endpush
@endif
