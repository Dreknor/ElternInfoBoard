@if($careChildren && $careChildren->count() > 0)
    @php
        $krankmeldungActive = $modules->where('setting', 'Krankmeldung')->isNotEmpty() && auth()->user()->can('view krankmeldung');
        $schickzeitenActive = $modules->where('setting', 'Schickzeiten')->isNotEmpty() && auth()->user()->can('view schickzeiten');
        $schickzeitenSetting ??= null;
    @endphp
    @once
        @push('css')
            <style>
                .quick-btn { min-height: 48px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .15rem; padding: .35rem .25rem; border-radius: .5rem; border: 1px solid var(--color-card-border); background: var(--color-card-bg); color: var(--color-text-primary); font-size: .8rem; font-weight: 600; line-height: 1.1; }
                .quick-btn i { font-size: 1rem; color: var(--color-primary); }
                .quick-btn:hover { border-color: var(--color-primary); }
                .quick-btn.is-active { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
                .quick-btn.is-active i { color: #fff; }
                .quick-panel { display: flex; flex-direction: column; gap: .4rem; padding: .75rem; border-radius: .5rem; background: var(--color-surface-subtle); }
                .quick-label { font-size: .8rem; font-weight: 600; color: var(--color-text-primary); margin: 0; }
                .quick-input { width: 100%; padding: .45rem .6rem; border: 1px solid var(--color-input-border); border-radius: .4rem; background: var(--color-input-bg); color: var(--color-text-primary); }
                .quick-row { display: flex; flex-wrap: wrap; gap: .5rem; }
                .quick-row > div { flex: 1 1 7rem; }
                .quick-segment { display: flex; border: 1px solid var(--color-input-border); border-radius: .4rem; overflow: hidden; }
                .quick-segment label { flex: 1; min-height: 44px; display: flex; align-items: center; justify-content: center; margin: 0; font-size: .85rem; cursor: pointer; color: var(--color-text-primary); background: var(--color-input-bg); }
                .quick-segment label.is-active { background: var(--color-primary); color: #fff; font-weight: 600; }
                .quick-segment label:has(input:focus-visible) { outline: 2px solid var(--color-primary); outline-offset: -2px; }
                .quick-hint { font-size: .75rem; color: var(--color-text-secondary); margin: 0; }
                .quick-submit { min-height: 48px; width: 100%; margin-top: .25rem; border-radius: .5rem; font-weight: 600; color: #fff; background: var(--color-primary); }
                .quick-submit:hover { background: var(--color-primary-dark); }
                .quick-link { font-size: .75rem; color: var(--color-primary); }
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
                <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(min(100%, 16rem), 1fr));">
                    @foreach($careChildren as $child)
                        @php
                            $todayCheckIn = $child->checkIns->first();
                            $isCheckedIn = $todayCheckIn && $todayCheckIn->checked_in && !$todayCheckIn->checked_out;
                            $isCheckedOut = $todayCheckIn && $todayCheckIn->checked_out;
                            $hasKrankmeldung = $child->krankmeldungen->count() > 0;
                            $schickzeitenToday = $child->getSchickzeitenForToday();
                        @endphp
                        <div class="flex flex-col rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200" style="border: 1px solid var(--color-card-border);">
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
                            @php
                                $schickzeitText = $schickzeitenToday->map(function ($sz) {
                                    if ($sz->type == 'genau' || ($sz->time && is_null($sz->time_ab) && is_null($sz->time_spaet))) {
                                        return 'genau '.$sz->time?->format('H:i').' Uhr';
                                    }
                                    return trim(
                                        ($sz->time_ab ? 'ab '.$sz->time_ab->format('H:i').' Uhr' : '')
                                        .($sz->time_ab && $sz->time_spaet ? ', ' : '')
                                        .($sz->time_spaet ? 'spätestens '.$sz->time_spaet->format('H:i').' Uhr' : '')
                                    );
                                })->filter()->implode(' · ');
                                $noticeToday = $child->noticeToday();
                                $canSick = !$hasKrankmeldung && $krankmeldungActive && auth()->user()->can('reportSick', $child);
                                $canSchickzeit = !$hasKrankmeldung && !$isCheckedOut && $schickzeitenActive && $schickzeitenSetting && auth()->user()->can('manage', $child);
                                $canNotice = !$isCheckedOut && auth()->user()->can('manage', $child);
                                $actionCount = (int) $canSick + (int) $canSchickzeit + (int) $canNotice;
                            @endphp
                            <div class="flex-1 p-3 space-y-3" style="background: var(--color-widget-body-bg);" x-data="{ open: null }">
                                {{-- Status --}}
                                @if($hasKrankmeldung)
                                    <div class="flex items-center gap-2 px-3 py-2 bg-red-50 border-l-4 border-red-500 rounded">
                                        <i class="fas fa-notes-medical text-red-600" aria-hidden="true"></i>
                                        <p class="text-red-800 font-semibold text-sm mb-0">Krankgemeldet</p>
                                    </div>
                                @elseif($isCheckedOut)
                                    <div class="flex items-center gap-2 px-3 py-2 bg-gray-100 border-l-4 border-gray-400 rounded">
                                        <i class="fas fa-sign-out-alt text-gray-600" aria-hidden="true"></i>
                                        <p class="font-semibold text-sm mb-0" style="color: var(--color-text-primary);">Abgeholt um {{$todayCheckIn?->updated_at?->format('H:i')}} Uhr</p>
                                    </div>
                                @elseif($isCheckedIn)
                                    <div class="flex items-center gap-2 px-3 py-2 rounded"
                                         style="background: var(--color-widget-success-bg); border-left: 4px solid var(--color-widget-success-from);">
                                        <i class="fas fa-user-check" style="color: var(--color-widget-success-from);" aria-hidden="true"></i>
                                        <p class="font-semibold text-sm mb-0" style="color: var(--color-widget-success-border);">Angemeldet</p>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 px-3 py-2 bg-gray-100 border-l-4 border-gray-400 rounded">
                                        <i class="fas fa-times-circle text-gray-600" aria-hidden="true"></i>
                                        <p class="font-semibold text-sm mb-0" style="color: var(--color-text-primary);">Nicht angemeldet</p>
                                    </div>
                                @endif

                                {{-- Heute: Schickzeit und Notiz als schlichte Zeilen --}}
                                @if(!$hasKrankmeldung && !$isCheckedOut || $noticeToday)
                                    <dl class="text-sm space-y-1 mb-0" style="color: var(--color-text-primary);">
                                        @if(!$hasKrankmeldung && !$isCheckedOut)
                                            <div class="flex gap-2">
                                                <dt class="shrink-0"><i class="far fa-clock w-4 text-center" style="color: var(--color-text-secondary);" aria-hidden="true"></i><span class="sr-only">Schickzeit</span></dt>
                                                <dd class="mb-0">
                                                    @if($schickzeitText !== '')
                                                        Schickzeit {{ $schickzeitText }}
                                                    @else
                                                        <span style="color: var(--color-text-secondary);">Keine Schickzeit für heute</span>
                                                    @endif
                                                </dd>
                                            </div>
                                        @endif
                                        @if($noticeToday)
                                            <div class="flex gap-2">
                                                <dt class="shrink-0"><i class="far fa-sticky-note w-4 text-center" style="color: var(--color-text-secondary);" aria-hidden="true"></i><span class="sr-only">Notiz</span></dt>
                                                <dd class="mb-0">{{ $noticeToday->notice }}</dd>
                                            </div>
                                        @endif
                                    </dl>
                                @endif

                                {{-- Schnellerfassung: eine Leiste, immer nur ein Formular offen --}}
                                @if($actionCount > 0)
                                    <div class="grid gap-2 pt-3 {{ $actionCount === 3 ? 'grid-cols-3' : ($actionCount === 2 ? 'grid-cols-2' : 'grid-cols-1') }}"
                                         style="border-top: 1px solid var(--color-card-border);">
                                        @if($canSick)
                                            <button type="button" class="quick-btn" @click="open = open === 'sick' ? null : 'sick'"
                                                    :class="{ 'is-active': open === 'sick' }" :aria-expanded="open === 'sick'" aria-controls="qa-sick-{{ $child->id }}">
                                                <i class="fas fa-notes-medical" aria-hidden="true"></i><span>Krank</span>
                                            </button>
                                        @endif
                                        @if($canSchickzeit)
                                            <button type="button" class="quick-btn" @click="open = open === 'sz' ? null : 'sz'"
                                                    :class="{ 'is-active': open === 'sz' }" :aria-expanded="open === 'sz'" aria-controls="qa-sz-{{ $child->id }}">
                                                <i class="far fa-clock" aria-hidden="true"></i><span>Schickzeit</span>
                                            </button>
                                        @endif
                                        @if($canNotice)
                                            <button type="button" class="quick-btn" @click="open = open === 'notice' ? null : 'notice'"
                                                    :class="{ 'is-active': open === 'notice' }" :aria-expanded="open === 'notice'" aria-controls="qa-notice-{{ $child->id }}">
                                                <i class="far fa-sticky-note" aria-hidden="true"></i><span>Notiz</span>
                                            </button>
                                        @endif
                                    </div>

                                    @if($canSick)
                                        <form id="qa-sick-{{ $child->id }}" x-show="open === 'sick'" x-cloak
                                              action="{{ url('krankmeldung') }}" method="post" class="quick-panel">
                                            @csrf
                                            <input type="hidden" name="child_id" value="{{ $child->id }}">
                                            <input type="hidden" name="start" value="{{ today()->toDateString() }}">
                                            <label for="sick-ende-{{ $child->id }}" class="quick-label">Krank ab heute bis einschließlich</label>
                                            <input type="date" id="sick-ende-{{ $child->id }}" name="ende" required
                                                   value="{{ today()->toDateString() }}" min="{{ today()->toDateString() }}" class="quick-input">
                                            <label for="sick-kommentar-{{ $child->id }}" class="quick-label">Hinweis</label>
                                            <input type="text" id="sick-kommentar-{{ $child->id }}" name="kommentar" required maxlength="10000"
                                                   value="Krank" class="quick-input">
                                            <button type="submit" class="quick-submit">Krankmeldung senden</button>
                                            <a href="{{ url('krankmeldung') }}" class="quick-link">Meldepflichtige Krankheit oder Anhang? Zum ausführlichen Formular</a>
                                        </form>
                                    @endif

                                    @if($canSchickzeit)
                                        @php
                                            $timeAttrs = 'min="'.e($schickzeitenSetting->schicken_ab).'" max="'.e($schickzeitenSetting->schicken_bis).'" step="'.((int) $schickzeitenSetting->schicken_intervall * 60).'"';
                                        @endphp
                                        <form id="qa-sz-{{ $child->id }}" x-show="open === 'sz'" x-cloak x-data="{ type: 'genau' }"
                                              action="{{ route('schickzeiten.store') }}" method="post" class="quick-panel">
                                            @csrf
                                            <input type="hidden" name="child_id" value="{{ $child->id }}">
                                            <input type="hidden" name="specific_date" value="{{ today()->toDateString() }}">
                                            <input type="hidden" name="redirect_back" value="1">
                                            @if($schickzeitenSetting->schicken_erlaube_zeitraum)
                                                <div class="quick-segment" role="radiogroup" aria-label="Art der Schickzeit">
                                                    <label :class="{ 'is-active': type === 'genau' }"><input type="radio" name="type" value="genau" x-model="type" class="sr-only" checked>genau um</label>
                                                    <label :class="{ 'is-active': type === 'ab' }"><input type="radio" name="type" value="ab" x-model="type" class="sr-only">Zeitraum</label>
                                                </div>
                                            @else
                                                <input type="hidden" name="type" value="genau">
                                            @endif
                                            <div x-show="type === 'genau'">
                                                <label for="sz-time-{{ $child->id }}" class="quick-label">Uhrzeit</label>
                                                <input type="time" id="sz-time-{{ $child->id }}" name="time" {!! $timeAttrs !!}
                                                       class="quick-input" x-bind:required="type === 'genau'" x-bind:disabled="type !== 'genau'">
                                            </div>
                                            @if($schickzeitenSetting->schicken_erlaube_zeitraum)
                                                <div x-show="type === 'ab'" x-cloak class="quick-row">
                                                    <div>
                                                        <label for="sz-ab-{{ $child->id }}" class="quick-label">ab</label>
                                                        <input type="time" id="sz-ab-{{ $child->id }}" name="time_ab" {!! $timeAttrs !!}
                                                               class="quick-input" x-bind:required="type === 'ab'" x-bind:disabled="type !== 'ab'">
                                                    </div>
                                                    <div>
                                                        <label for="sz-spaet-{{ $child->id }}" class="quick-label">spätestens</label>
                                                        <input type="time" id="sz-spaet-{{ $child->id }}" name="time_spaet" {!! $timeAttrs !!}
                                                               class="quick-input" x-bind:disabled="type !== 'ab'">
                                                    </div>
                                                </div>
                                            @endif
                                            <p class="quick-hint">Nur für heute, {{ $schickzeitenSetting->schicken_ab }}–{{ $schickzeitenSetting->schicken_bis }} Uhr.</p>
                                            <button type="submit" class="quick-submit">Schickzeit speichern</button>
                                        </form>
                                    @endif

                                    @if($canNotice)
                                        <form id="qa-notice-{{ $child->id }}" x-show="open === 'notice'" x-cloak
                                              action="{{ route('child.notice.store', $child) }}" method="post" class="quick-panel">
                                            @csrf
                                            <input type="hidden" name="date" value="{{ today()->toDateString() }}">
                                            <input type="hidden" name="redirect_back" value="1">
                                            <label for="notice-{{ $child->id }}" class="quick-label">Notiz an den Hort für heute</label>
                                            <textarea id="notice-{{ $child->id }}" name="notice" rows="2" maxlength="500"
                                                      placeholder="z. B. Wird heute von Oma abgeholt" class="quick-input">{{ $noticeToday?->notice }}</textarea>
                                            @if($noticeToday)
                                                <p class="quick-hint">Leer speichern entfernt die Notiz.</p>
                                            @endif
                                            <button type="submit" class="quick-submit">Notiz speichern</button>
                                        </form>
                                    @endif
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
