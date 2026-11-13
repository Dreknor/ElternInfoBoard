@if($careChildren && $careChildren->count() > 0)
    @php
        $krankmeldungActive = $modules->where('setting', 'Krankmeldung')->isNotEmpty() && auth()->user()->can('view krankmeldung');
        $schickzeitenActive = $modules->where('setting', 'Schickzeiten')->isNotEmpty() && auth()->user()->can('view schickzeiten');
        $schickzeitenSetting ??= null;
    @endphp
    @once
        @push('css')
            <style>
                .quick-action-summary { min-height: 48px; display: flex; align-items: center; gap: .5rem; padding: .5rem .75rem; cursor: pointer; font-weight: 600; font-size: .875rem; color: var(--color-text-primary); list-style: none; }
                .quick-action-summary::-webkit-details-marker { display: none; }
                .quick-action-summary::after { content: '\25B8'; margin-left: auto; }
                .quick-action[open] > .quick-action-summary::after { content: '\25BE'; }
                .quick-action-input { width: 100%; padding: .5rem .75rem; border: 2px solid var(--color-input-border); border-radius: .5rem; background: var(--color-input-bg); color: var(--color-text-primary); }
                .quick-action-choice { min-height: 48px; color: var(--color-text-primary); }
                .quick-action-submit { min-height: 48px; width: 100%; padding: .5rem 1rem; border-radius: .5rem; font-weight: 600; color: #fff; background: var(--color-primary); }
                .quick-action-submit:hover { background: var(--color-primary-dark); }
                [x-cloak] { display: none !important; }
            </style>
        @endpush
    @endonce
    <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
            <div class="px-4 py-3 border-b"
                 style="background: linear-gradient(to right, var(--color-widget-success-from), var(--color-widget-success-to)); border-color: var(--color-widget-success-border);">
                <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
                    <i class="fas fa-user-check"></i>
                    CheckIn-Status Ihrer Kinder
                </h5>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($careChildren as $child)
                        @php
                            $todayCheckIn = $child->checkIns->first();
                            $isCheckedIn = $todayCheckIn && $todayCheckIn->checked_in && !$todayCheckIn->checked_out;
                            $isCheckedOut = $todayCheckIn && $todayCheckIn->checked_out;
                            $hasKrankmeldung = $child->krankmeldungen->count() > 0;
                            $schickzeitenToday = $child->getSchickzeitenForToday();
                        @endphp
                        <div class="rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200" style="border: 1px solid var(--color-card-border);">
                            <!-- Header -->
                            <div class="px-4 py-3 text-white"
                                 style="background: linear-gradient(to right, {{ $isCheckedIn ? 'var(--color-widget-success-from), var(--color-widget-success-to)' : 'var(--color-widget-warning-from), var(--color-widget-warning-to)' }});">
                                <h6 class="font-bold text-base mb-0">
                                    {{$child->first_name}} {{$child->last_name}}
                                </h6>
                                @if($child->group)
                                    <p class="text-xs opacity-90 mb-0">{{$child->group->name}}</p>
                                @endif
                            </div>

                            <!-- Body -->
                            <div class="p-4" style="background: var(--color-widget-body-bg);">
                                @if($hasKrankmeldung)
                                    <div class="flex items-start gap-2 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                                        <i class="fas fa-notes-medical text-red-600 mt-1"></i>
                                        <div>
                                            <p class="text-red-800 font-semibold text-sm mb-0">Krankgemeldet</p>
                                            <p class="text-red-600 text-xs mb-0">Heute nicht in der Einrichtung</p>
                                        </div>
                                    </div>
                                @elseif($isCheckedOut)
                                    <div class="flex items-start gap-2 p-3 bg-gray-100 border-l-4 border-gray-400 rounded">
                                        <i class="fas fa-sign-out-alt text-gray-600 mt-1"></i>
                                        <div>
                                            <p class="font-semibold text-sm mb-0" style="color: var(--color-text-primary);">Abgemeldet</p>
                                            <p class="text-xs mb-0" style="color: var(--color-text-secondary);">
                                                um {{$todayCheckIn?->updated_at?->format('H:i')}} Uhr
                                            </p>
                                        </div>
                                    </div>
                                @elseif($isCheckedIn)
                                    <div class="space-y-3">
                                        <div class="flex items-start gap-2 p-3 rounded"
                                             style="background: var(--color-widget-success-bg); border-left: 4px solid var(--color-widget-success-from);">
                                            <i class="fas fa-user-check mt-1" style="color: var(--color-widget-success-from);"></i>
                                            <div class="flex-1">
                                                <p class="font-semibold text-sm mb-0" style="color: var(--color-widget-success-border);">Angemeldet</p>
                                                <p class="text-xs mb-0" style="color: var(--color-widget-success-to);">Derzeit in der Einrichtung</p>
                                            </div>
                                        </div>

                                    </div>
                                @else
                                    <div class="flex items-start gap-2 p-3 bg-gray-100 border-l-4 border-gray-400 rounded">
                                        <i class="fas fa-times-circle text-gray-600 mt-1"></i>
                                        <div>
                                            <p class="font-semibold text-sm mb-0" style="color: var(--color-text-primary);">Nicht angemeldet</p>
                                            <p class="text-xs mb-0" style="color: var(--color-text-secondary);">Heute noch nicht eingecheckt</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Schickzeit auch vor dem Einchecken zeigen (z. B. morgens zur Kontrolle) --}}
                                @if(!$hasKrankmeldung && !$isCheckedOut)
                                    <div class="mt-3">
                                    @if($schickzeitenToday->count() > 0)
                                        <div class="rounded p-3" style="background: var(--color-widget-accent-bg); border: 1px solid var(--color-widget-accent-border);">
                                            <div class="flex items-center gap-2 mb-2">
                                                <i class="far fa-clock text-sm" style="color: var(--color-widget-accent-from);"></i>
                                                <span class="font-semibold text-sm" style="color: var(--color-widget-accent-border);">Schickzeit heute:</span>
                                            </div>
                                            @foreach($schickzeitenToday as $schickzeit)
                                                <p class="text-sm mb-0 ml-6" style="color: var(--color-widget-accent-to);">
                                                    @if($schickzeit->type == 'genau' || ($schickzeit->time && is_null($schickzeit->time_ab) && is_null($schickzeit->time_spaet)))
                                                        <i class="fas fa-dot-circle text-xs mr-1"></i>
                                                        Genau {{$schickzeit->time?->format('H:i')}} Uhr
                                                    @else
                                                        <i class="fas fa-arrow-right text-xs mr-1"></i>
                                                        @if(!is_null($schickzeit->time_ab)) Ab {{$schickzeit->time_ab?->format('H:i')}} Uhr @endif
                                                        @if(!is_null($schickzeit->time_ab) && !is_null($schickzeit->time_spaet)) - @endif
                                                        @if(!is_null($schickzeit->time_spaet)) Spät. {{$schickzeit->time_spaet?->format('H:i')}} Uhr @endif
                                                    @endif
                                                </p>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="rounded p-2" style="background: var(--color-card-border)20; border: 1px solid var(--color-card-border);">
                                            <p class="text-xs mb-0 text-center" style="color: var(--color-text-secondary);">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Keine Schickzeit für heute hinterlegt
                                            </p>
                                        </div>
                                    @endif
                                    </div>
                                @endif

                                {{-- Schnellerfassung für heute: Krankmeldung, Schickzeit, Notiz --}}
                                @php
                                    $canSick = !$hasKrankmeldung && $krankmeldungActive && auth()->user()->can('reportSick', $child);
                                    $canSchickzeit = !$hasKrankmeldung && !$isCheckedOut && $schickzeitenActive && $schickzeitenSetting && auth()->user()->can('manage', $child);
                                    $canNotice = !$isCheckedOut && auth()->user()->can('manage', $child);
                                    $noticeToday = $child->noticeToday();
                                @endphp
                                @if($canSick || $canSchickzeit || $canNotice)
                                    <div class="mt-3 pt-3 space-y-2" style="border-top: 1px solid var(--color-card-border);">
                                        <p class="text-xs font-semibold uppercase mb-1" style="color: var(--color-text-secondary);">Für heute erfassen</p>

                                        @if($noticeToday)
                                            <div class="rounded p-2 text-sm" style="background: var(--color-surface-subtle); color: var(--color-text-primary);">
                                                <i class="far fa-sticky-note mr-1" aria-hidden="true"></i>
                                                <span class="font-semibold">Notiz:</span> {{ $noticeToday->notice }}
                                            </div>
                                        @endif

                                        @if($canSick)
                                            <details class="quick-action rounded" style="border: 1px solid var(--color-card-border);">
                                                <summary class="quick-action-summary">
                                                    <i class="fas fa-notes-medical" aria-hidden="true"></i> Krankmelden
                                                </summary>
                                                <form action="{{ url('krankmeldung') }}" method="post" class="p-3 space-y-2">
                                                    @csrf
                                                    <input type="hidden" name="child_id" value="{{ $child->id }}">
                                                    <input type="hidden" name="start" value="{{ today()->toDateString() }}">
                                                    <div>
                                                        <label for="sick-ende-{{ $child->id }}" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Voraussichtlich krank bis einschließlich</label>
                                                        <input type="date" id="sick-ende-{{ $child->id }}" name="ende" required
                                                               value="{{ today()->toDateString() }}" min="{{ today()->toDateString() }}"
                                                               class="quick-action-input">
                                                    </div>
                                                    <div>
                                                        <label for="sick-kommentar-{{ $child->id }}" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Hinweis</label>
                                                        <textarea id="sick-kommentar-{{ $child->id }}" name="kommentar" rows="2" required maxlength="10000"
                                                                  class="quick-action-input">Krank</textarea>
                                                    </div>
                                                    <button type="submit" class="quick-action-submit">Krankmeldung senden</button>
                                                    <a href="{{ url('krankmeldung') }}" class="block text-xs mt-1" style="color: var(--color-primary);">
                                                        Mit meldepflichtiger Krankheit oder Anhang: ausführliches Formular
                                                    </a>
                                                </form>
                                            </details>
                                        @endif

                                        @if($canSchickzeit)
                                            <details class="quick-action rounded" style="border: 1px solid var(--color-card-border);">
                                                <summary class="quick-action-summary">
                                                    <i class="far fa-clock" aria-hidden="true"></i> Schickzeit für heute
                                                </summary>
                                                <form action="{{ route('schickzeiten.store') }}" method="post" class="p-3 space-y-2"
                                                      x-data="{ type: 'genau' }">
                                                    @csrf
                                                    <input type="hidden" name="child_id" value="{{ $child->id }}">
                                                    <input type="hidden" name="specific_date" value="{{ today()->toDateString() }}">
                                                    <input type="hidden" name="redirect_back" value="1">
                                                    @if($schickzeitenSetting->schicken_erlaube_zeitraum)
                                                        <fieldset>
                                                            <legend class="text-sm font-medium mb-1" style="color: var(--color-text-primary);">Art</legend>
                                                            <label class="inline-flex items-center gap-2 mr-4 quick-action-choice">
                                                                <input type="radio" name="type" value="genau" x-model="type" checked> genau um
                                                            </label>
                                                            <label class="inline-flex items-center gap-2 quick-action-choice">
                                                                <input type="radio" name="type" value="ab" x-model="type"> Zeitraum
                                                            </label>
                                                        </fieldset>
                                                    @else
                                                        <input type="hidden" name="type" value="genau">
                                                    @endif
                                                    @php
                                                        $timeAttrs = 'min="'.e($schickzeitenSetting->schicken_ab).'" max="'.e($schickzeitenSetting->schicken_bis).'" step="'.((int) $schickzeitenSetting->schicken_intervall * 60).'"';
                                                    @endphp
                                                    <div x-show="type === 'genau'">
                                                        <label for="sz-time-{{ $child->id }}" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Uhrzeit</label>
                                                        <input type="time" id="sz-time-{{ $child->id }}" name="time" {!! $timeAttrs !!}
                                                               class="quick-action-input" x-bind:required="type === 'genau'" x-bind:disabled="type !== 'genau'">
                                                    </div>
                                                    @if($schickzeitenSetting->schicken_erlaube_zeitraum)
                                                        <div x-show="type === 'ab'" x-cloak class="space-y-2">
                                                            <div>
                                                                <label for="sz-ab-{{ $child->id }}" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Frühestens ab</label>
                                                                <input type="time" id="sz-ab-{{ $child->id }}" name="time_ab" {!! $timeAttrs !!}
                                                                       class="quick-action-input" x-bind:required="type === 'ab'" x-bind:disabled="type !== 'ab'">
                                                            </div>
                                                            <div>
                                                                <label for="sz-spaet-{{ $child->id }}" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Spätestens (optional)</label>
                                                                <input type="time" id="sz-spaet-{{ $child->id }}" name="time_spaet" {!! $timeAttrs !!}
                                                                       class="quick-action-input" x-bind:disabled="type !== 'ab'">
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <p class="text-xs mb-0" style="color: var(--color-text-secondary);">
                                                        Erlaubt: {{ $schickzeitenSetting->schicken_ab }}–{{ $schickzeitenSetting->schicken_bis }} Uhr. Ersetzt die Schickzeit für heute.
                                                    </p>
                                                    <button type="submit" class="quick-action-submit">Schickzeit speichern</button>
                                                </form>
                                            </details>
                                        @endif

                                        @if($canNotice)
                                            <details class="quick-action rounded" style="border: 1px solid var(--color-card-border);">
                                                <summary class="quick-action-summary">
                                                    <i class="far fa-sticky-note" aria-hidden="true"></i> {{ $noticeToday ? 'Notiz ändern' : 'Notiz für heute' }}
                                                </summary>
                                                <form action="{{ route('child.notice.store', $child) }}" method="post" class="p-3 space-y-2">
                                                    @csrf
                                                    <input type="hidden" name="date" value="{{ today()->toDateString() }}">
                                                    <input type="hidden" name="redirect_back" value="1">
                                                    <div>
                                                        <label for="notice-{{ $child->id }}" class="block text-sm font-medium mb-1" style="color: var(--color-text-primary);">Notiz an den Hort</label>
                                                        <textarea id="notice-{{ $child->id }}" name="notice" rows="2" maxlength="500"
                                                                  placeholder="z. B. Wird heute von Oma abgeholt"
                                                                  class="quick-action-input">{{ $noticeToday?->notice }}</textarea>
                                                        @if($noticeToday)
                                                            <p class="text-xs mt-1 mb-0" style="color: var(--color-text-secondary);">Leer speichern entfernt die Notiz.</p>
                                                        @endif
                                                    </div>
                                                    <button type="submit" class="quick-action-submit">Notiz speichern</button>
                                                </form>
                                            </details>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @can('view child')
                    <div class="text-center mt-4 pt-4" style="border-top: 1px solid var(--color-card-border);">
                        <a href="{{ url('/care/children') }}"
                           class="inline-flex items-center px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                           style="background: var(--color-widget-success-from);"
                           onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-to')"
                           onmouseout="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-from')">
                            <i class="fas fa-child mr-2"></i>
                            Zur Kinderübersicht
                        </a>
                    </div>
                @endcan
            </div>
    </div>
@endif
