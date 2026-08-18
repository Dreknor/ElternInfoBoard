@extends('layouts.app')
@section('title') - Reinigung @endsection

@section('content')
    <div class="w-full max-w-7xl mx-auto px-2 sm:px-4 py-4 sm:py-6 space-y-6">

        {{-- Seitentitel --}}
        <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
            <div class="px-6 py-4 border-b" style="background: var(--color-main-header-bg); border-color: var(--color-main-header-bg);">
                <h2 class="text-2xl font-bold flex items-center gap-3 mb-0" style="color: #ffffff;">
                    <i class="fas fa-broom"></i>
                    Reinigungsplan
                </h2>
            </div>
        </div>

        @if(count($Bereiche) < 1)
            <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
                <div class="text-center py-5 px-4">
                    <i class="fas fa-broom" style="font-size: 3rem; color: var(--color-card-border);"></i>
                    <p class="mt-3 mb-0" style="color: var(--color-text-secondary);">
                        Die Reinigungsliste wird mit Beginn des Schuljahres angezeigt.
                    </p>
                </div>
            </div>
        @endif

        {{-- Eigene Reinigungstermine --}}
        @if($user?->reinigung()->whereDate('datum', '>', Carbon\Carbon::yesterday())->count() > 0 or (!is_null($user->sorgeberechtigter2) and $user->sorgeberechtigter2->reinigung()->whereDate('datum', '>', Carbon\Carbon::yesterday())->count() > 0))
            <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
                <div class="px-4 py-3 border-b"
                     style="background: linear-gradient(to right, var(--color-widget-warning-from), var(--color-widget-warning-to)); border-color: var(--color-widget-warning-border);">
                    <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
                        <i class="fas fa-user-clock"></i>
                        Eigene Reinigungstermine
                    </h5>
                </div>
                <div class="p-4">
                    <div class="space-y-2">
                        @foreach($user?->reinigung()->whereDate('datum', '>', Carbon\Carbon::yesterday())->get() as $reinigung)
                            <div class="p-3 rounded-lg d-flex align-items-center gap-2" style="background: var(--color-widget-body-bg); border: 1px solid var(--color-card-border);">
                                <i class="far fa-calendar-alt" style="color: var(--color-widget-warning-from);"></i>
                                <span style="color: var(--color-text-primary);">
                                    Woche: {{$reinigung->datum->startOfWeek()->format('d.m.')}} - {{$reinigung->datum->endOfWeek()->format('d.m.Y')}}
                                </span>
                            </div>
                        @endforeach
                        @if(!is_null($user->sorg2) and !is_null($user->sorgeberechtigter2))
                            @foreach($user?->sorgeberechtigter2?->reinigung()->whereDate('datum', '>', Carbon\Carbon::yesterday())->get() as $reinigung)
                                <div class="p-3 rounded-lg d-flex align-items-center gap-2" style="background: var(--color-widget-body-bg); border: 1px solid var(--color-card-border);">
                                    <i class="far fa-calendar-alt" style="color: var(--color-widget-warning-from);"></i>
                                    <span style="color: var(--color-text-primary);">
                                        Woche: {{$reinigung->datum->startOfWeek()->format('d.m.')}} - {{$reinigung->datum->endOfWeek()->format('d.m.Y')}}
                                    </span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Reinigungspläne je Bereich --}}
        @foreach($Bereiche as $Bereich)
            <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
                <div class="px-4 py-3 border-b d-flex justify-content-between align-items-center flex-wrap gap-2"
                     style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border);">
                    <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
                        <i class="fas fa-broom"></i>
                        Reinigungsplan {{$Bereich === \App\Model\Reinigung::BEREICH_GESAMT ? '(gesamte Einrichtung)' : $Bereich}}
                    </h5>
                    @can('edit reinigung')
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{url('reinigung/'.$Bereich.'/export')}}"
                               class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium rounded-lg transition-colors duration-200 text-decoration-none"
                               style="background: rgba(255,255,255,0.15); color: var(--color-widget-header-text); border: 1px solid rgba(255,255,255,0.3);"
                               onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                               onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                                <i class="fas fa-file-export"></i>
                                <span class="hidden sm:inline">Export</span>
                            </a>
                            <a href="{{url('reinigung/'.$Bereich.'/auto')}}"
                               class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium rounded-lg transition-colors duration-200 text-decoration-none"
                               style="background: rgba(255,255,255,0.15); color: var(--color-widget-header-text); border: 1px solid rgba(255,255,255,0.3);"
                               onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                               onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                                <i class="fas fa-magic"></i>
                                <span class="hidden sm:inline">füllen</span>
                            </a>
                        </div>
                    @endcan
                </div>

                <div class="p-4 space-y-3">
                    @for($Woche = $datum->copy(); $Woche->lessThanOrEqualTo($ende); $Woche->addWeek())
                        @php
                            $wocheEintraege = $Familien[$Bereich]->filter(function ($value) use ($Woche) {
                                if ($Woche->startOfWeek()->eq($value->datum->startOfWeek())) {
                                    return $value;
                                }
                            });
                        @endphp
                        <div class="rounded-lg overflow-hidden" style="border: 1px solid var(--color-card-border);">
                            <div class="px-4 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2"
                                 style="background: var(--color-widget-body-bg);">
                                <span class="font-semibold d-flex align-items-center gap-2" style="color: var(--color-text-primary);">
                                    <i class="far fa-calendar-alt" style="color: var(--color-widget-primary-from);"></i>
                                    {{$Woche->copy()->startOfWeek()->format('d.m.')}} - {{$Woche->copy()->endOfWeek()->format('d.m.Y')}}
                                </span>
                                @can('edit reinigung')
                                    <a href="{{url('reinigung/create/'.$Bereich.'/'.$Woche->startOfWeek()->format('Ymd'))}}"
                                       class="inline-flex items-center gap-1 px-2 py-1 text-sm rounded-lg text-white transition-colors duration-200"
                                       style="background: var(--color-widget-primary-from);"
                                       onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-primary-to')"
                                       onmouseout="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-primary-from')">
                                        <i class="fas fa-plus-circle"></i>
                                    </a>
                                @endcan
                            </div>
                            <div class="p-3" style="background: var(--color-card-bg);">
                                @if($wocheEintraege->count() < 1)
                                    <p class="text-sm mb-0" style="color: var(--color-text-secondary);">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Noch keine Zuteilung für diese Woche.
                                    </p>
                                @else
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach($wocheEintraege as $reinigung)
                                            @php
                                                $isOwn = $reinigung->user->id == auth()->id() or auth()->user()->sorg2 == auth()->id();
                                            @endphp
                                            <div class="rounded-lg p-3"
                                                 style="background: {{ $isOwn ? 'var(--color-widget-warning-bg)' : 'var(--color-widget-body-bg)' }};
                                                        border: 1px solid {{ $isOwn ? 'var(--color-widget-warning-from)' : 'var(--color-card-border)' }};">
                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                    <span class="font-semibold d-flex align-items-center flex-wrap gap-1" style="color: var(--color-text-primary);">
                                                        @can('edit reinigung')
                                                            {{$reinigung->user->name}}
                                                        @else
                                                            Familie {{$reinigung->user->familie_name}}
                                                        @endcan
                                                        @if($isOwn)
                                                            <span class="badge badge-warning badge-pill">Ihr Termin</span>
                                                        @endif
                                                    </span>
                                                    @can('edit reinigung')
                                                        <form action="{{url('reinigung/'.$Bereich.'/'.$reinigung->id.'/trash')}}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-danger" style="background: none; border: none;">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </div>
                                                <div class="text-sm mt-1" style="color: var(--color-text-secondary);">
                                                    <i class="fas fa-clipboard-list mr-1"></i>{{$reinigung->aufgabe}}
                                                </div>
                                                @if($reinigung->bemerkung)
                                                    <div class="text-xs mt-1" style="color: var(--color-text-muted);">
                                                        <i class="fas fa-comment-dots mr-1"></i>{{$reinigung->bemerkung}}
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        @endforeach
    </div>

    @if($user->can('edit reinigung'))
        <div class="w-full max-w-7xl mx-auto px-2 sm:px-4 py-4 sm:py-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
                    <div class="px-4 py-3 border-b"
                         style="background: linear-gradient(to right, var(--color-widget-accent-from), var(--color-widget-accent-to)); border-color: var(--color-widget-accent-border);">
                        <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
                            <i class="fas fa-clipboard-list"></i>
                            Aufgaben
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="space-y-2">
                            @forelse($aufgaben as $task)
                                <div class="p-3 rounded-lg d-flex justify-content-between align-items-center" style="background: var(--color-widget-body-bg); border: 1px solid var(--color-card-border);">
                                    <span style="color: var(--color-text-primary);">{{$task->task}}</span>
                                    <form action="{{url('reinigung/task/'.$task->id.'/trash')}}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger" style="background: none; border: none;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm mb-0" style="color: var(--color-text-secondary);">
                                    Es wurden noch keine Aufgaben angelegt.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
                    <div class="px-4 py-3 border-b"
                         style="background: linear-gradient(to right, var(--color-widget-success-from), var(--color-widget-success-to)); border-color: var(--color-widget-success-border);">
                        <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
                            <i class="fas fa-plus-circle"></i>
                            Neue Aufgabe erstellen
                        </h5>
                    </div>
                    <div class="p-4">
                        <form action="{{url('reinigung/task/')}}" method="post">
                            @csrf
                            <label class="d-block mb-2" style="color: var(--color-text-primary);" for="task">
                                Aufgabe
                            </label>
                            <div class="d-flex gap-2 flex-wrap">
                                <input class="flex-1 px-3 py-2 rounded-lg outline-none" name="task" id="task" required
                                       style="border: 2px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);"
                                       onfocus="this.style.borderColor=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-from')"
                                       onblur="this.style.borderColor=getComputedStyle(document.documentElement).getPropertyValue('--color-input-border')">
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                                        style="background: var(--color-widget-success-from);"
                                        onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-to')"
                                        onmouseout="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-from')">
                                    <i class="fas fa-save"></i>
                                    speichern
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('js')
@endpush
