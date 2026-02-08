<!-- Child Anwesenheit Card - Modernisiert -->
<div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200 transition-all duration-300 hover:shadow-xl"
     x-data="{ showNoticeForm: false, showSchickzeiten: false }">

    <!-- Header mit Status -->
    <div class="px-4 py-3 @if($child->checkedIn()) bg-gradient-to-r from-teal-500 to-teal-600 @else bg-gradient-to-r from-amber-500 to-orange-500 @endif">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-white mb-0">
                {{$child->first_name}} {{$child->last_name}}
            </h3>
            <div>
                @if($child->checkedIn())
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white bg-opacity-30 text-green-400">
                        <i class="fas fa-check-circle mr-1"></i> Angemeldet
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white bg-opacity-30 text-red-300 ">
                        <i class="fas fa-times-circle mr-1"></i> Abgemeldet
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Status & Schickzeit Info -->
    <div class="p-4 @if($child->checkedIn()) bg-teal-50 @else bg-orange-50 @endif border-b border-gray-200">
        @if(!$child->checkedIn() and $child->checkIns()->where('date', today())->first())
            <div class="flex items-center gap-2 text-sm">
                <i class="fas fa-clock text-red-600"></i>
                <span class="text-gray-700">
                    <strong>{{$child->checkIns()->where('date', today())->first()?->updated_at?->format('H:i')}} Uhr</strong> abgemeldet
                </span>
            </div>
        @elseif($child->checkedIn())
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm text-teal-800">
                    <i class="fas fa-user-check text-teal-600"></i>
                    <span class="font-medium">Derzeit angemeldet</span>
                </div>

                @if($child->getSchickzeitenForToday()->count() > 0)
                    <div class="mt-2 pt-2 border-t border-teal-200">
                        <div class="text-xs font-semibold text-teal-700 mb-1">Heutige Schickzeit:</div>
                        @foreach($child->getSchickzeitenForToday() as $schickzeit)
                            <div class="flex items-center gap-2 text-sm text-gray-700">
                                @if($schickzeit->type == 'genau')
                                    <i class="fas fa-clock text-green-600"></i>
                                    <span>Genau <strong>{{$schickzeit->time?->format('H:i')}} Uhr</strong></span>
                                @else
                                    <i class="fas fa-hourglass-half text-amber-600"></i>
                                    <span>
                                        @if(!is_null($schickzeit->time_ab))
                                            Ab <strong>{{$schickzeit->time_ab?->format('H:i')}} Uhr</strong>
                                        @endif
                                        @if(!is_null($schickzeit->time_spaet))
                                            bis spätestens <strong>{{$schickzeit->time_spaet?->format('H:i')}} Uhr</strong>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center gap-2 text-xs text-amber-600 mt-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Keine Schickzeit hinterlegt</span>
                    </div>
                @endif
            </div>
        @else
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <i class="fas fa-info-circle"></i>
                <span>Heute nicht angemeldet</span>
            </div>
        @endif
    </div>

    <!-- Tagesaktuelle Schickzeiten (Collapsible) -->
    @if($child->schickzeiten->where('specific_date', '!=', NULL)->count() > 0)
        <div class="border-b border-gray-200">
            <button @click="showSchickzeiten = !showSchickzeiten" type="button"
                    class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors duration-150">
                <span class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <i class="fas fa-calendar-day text-purple-600"></i>
                    Tagesaktuelle Schickzeiten
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        {{$child->schickzeiten->where('specific_date', '!=', NULL)->count()}}
                    </span>
                </span>
                <i class="fas fa-chevron-down transition-transform duration-200" :class="{ 'rotate-180': showSchickzeiten }"></i>
            </button>

            <div x-show="showSchickzeiten"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 max-h-0"
                 x-transition:enter-end="opacity-100 max-h-screen"
                 style="display: none;"
                 class="px-4 pb-3">
                <ul class="space-y-2">
                    @foreach($child->schickzeiten->where('specific_date', '!=', NULL) as $schickzeit)
                        <li class="flex items-start justify-between p-3 bg-purple-50 rounded-lg">
                            <div>
                                <div class="font-medium text-gray-900 text-sm">{{$schickzeit->specific_date->format('d.m.Y')}}</div>
                                <div class="text-xs text-gray-600 mt-1">
                                    @if($schickzeit->type =="genau")
                                        <i class="fas fa-clock text-green-600"></i> Genau {{$schickzeit->time?->format('H:i')}} Uhr
                                    @else
                                        <i class="fas fa-hourglass-half text-amber-600"></i>
                                        Ab {{$schickzeit->time_ab?->format('H:i')}} Uhr
                                        @if(!is_null($schickzeit->time_ab) && $schickzeit->time_spaet)
                                            - {{$schickzeit->time_spaet?->format('H:i')}} Uhr
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <form action="{{route('schickzeiten.destroy', ['schickzeit' => $schickzeit->id])}}" method="post">
                                @csrf
                                @method('delete')
                                <button type="submit"
                                        class="p-2 text-red-600 hover:bg-red-100 rounded transition-colors duration-150"
                                        onclick="return confirm('Wirklich löschen?')">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Nachrichten/Notizen -->
    <div class="p-4">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <i class="fas fa-comment-alt text-blue-600"></i>
                Nachrichten
                @if($child->notice()->future()->count() > 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{$child->notice()->future()->count()}}
                    </span>
                @endif
            </h4>
            <button @click="showNoticeForm = !showNoticeForm" type="button"
                    class="p-2 text-blue-600 hover:bg-blue-100 rounded-full transition-colors duration-150">
                <i class="fas" :class="showNoticeForm ? 'fa-times' : 'fa-plus'"></i>
            </button>
        </div>

        <!-- Bestehende Notizen -->
        @if($child->notice()->future()->count() > 0)
            <div class="space-y-2 mb-3">
                @foreach($child->notice()->future()->get() as $notice)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg overflow-hidden">
                        <div class="px-3 py-2 bg-blue-100 flex items-center justify-between">
                            <span class="text-xs font-semibold text-blue-900">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                {{$notice->date->format('d.m.Y')}}
                            </span>
                            <form action="{{route('child.notice.destroy', ['childNotice' => $notice->id])}}" method="post">
                                @csrf
                                @method('delete')
                                <button type="button"
                                        class="p-1 text-red-600 hover:bg-red-100 rounded transition-colors duration-150 delete-notice-btn">
                                    <i class="fa fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-sm text-gray-700">{{$notice->notice}}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 italic mb-3">Keine Nachrichten hinterlegt</p>
        @endif

        <!-- Neue Notiz Form -->
        <div x-show="showNoticeForm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             style="display: none;"
             class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
            <h5 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-pencil-alt text-blue-600"></i>
                Neue Nachricht hinterlegen
            </h5>
            <form class="noticeForm" id="noticeForm_{{$child->id}}">
                @csrf
                <input type="hidden" name="child_id" value="{{$child->id}}">

                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-calendar-alt text-blue-600"></i> Datum
                    </label>
                    <input type="date" name="date"
                           value="{{\Carbon\Carbon::now()->format('Y-m-d')}}"
                           min="{{\Carbon\Carbon::now()->format('Y-m-d')}}"
                           class="w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-comment text-blue-600"></i> Nachricht
                    </label>
                    <textarea name="notice"
                              class="w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none resize-none"
                              rows="3"
                              placeholder="Nachricht hier eingeben...">{{$child->notice->first()?->notice}}</textarea>
                </div>

                <button type="button"
                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 form_submit">
                    <i class="fas fa-save"></i>
                    Nachricht speichern
                </button>
            </form>
        </div>
    </div>
</div>
