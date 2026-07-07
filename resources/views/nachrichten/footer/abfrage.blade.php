@if(!is_null($user->getRueckmeldung()) and !is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first()))
    @foreach($user->getRueckmeldung()->where('post_id', $nachricht->id)->all() as $rueckmeldung)
        <!-- Submitted Survey Response -->
        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden mb-4"
             x-data="{ editMode: false }">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-white" x-show="!editMode"></i>
                        <i class="fas fa-edit text-white" x-show="editMode"></i>
                    </div>
                    <h6 class="text-white font-semibold mb-0">
                        <span x-show="!editMode">Ihre Antworten zu: {{ $nachricht->rueckmeldung->text}}</span>
                        <span x-show="editMode">Antworten bearbeiten: {{ $nachricht->rueckmeldung->text}}</span>
                    </h6>
                </div>
                @if($nachricht->rueckmeldung->ende->gte(\Carbon\Carbon::today()))
                    <button type="button"
                            @click="editMode = !editMode"
                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fa" :class="editMode ? 'fa-times' : 'fa-edit'"></i>
                        <span class="hidden md:inline" x-text="editMode ? 'Abbrechen' : 'Bearbeiten'"></span>
                    </button>
                @endif
            </div>

            <!-- Answers List (read-only view) -->
            <div class="divide-y divide-gray-200" x-show="!editMode">
                @foreach($nachricht->rueckmeldung->options as $option)
                    <div class="px-4 py-3 hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                @if($option->type == "trenner")
                                    <h6 class="font-bold text-gray-900">{{$option->option}}</h6>
                                @else
                                    <p class="text-sm text-gray-700">{{$option->option}}</p>
                                @endif
                            </div>
                            <div class="flex-shrink-0">
                                @if($rueckmeldung->answers->where('option_id', $option->id)->first() != null)
                                    @switch($option->type)
                                        @case('text')
                                            <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-lg text-sm font-medium">
                                                {{$rueckmeldung->answers->where('option_id', $option->id)->first()->answer}}
                                            </span>
                                            @break
                                        @case('check')
                                            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                                <i class="fa fa-check text-white text-xs"></i>
                                            </div>
                                            @break
                                    @endswitch
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Inline Edit Form -->
            <div class="p-4" x-show="editMode" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0">
                <form method="POST" action="{{url('userrueckmeldung/'.$rueckmeldung->id)}}">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        @foreach($nachricht->rueckmeldung->options as $option)
                            @if($option->type == 'check')
                                <!-- Checkbox/Radio Option -->
                                <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                    <label class="flex items-center gap-3 w-full cursor-pointer @if($option->required == true) text-red-600 @endif">
                                        @if($nachricht->rueckmeldung->max_answers == 1)
                                            <input type="radio"
                                                   name="answers[options][]"
                                                   value="{{$option->id}}"
                                                   @if($option->required == true) required @endif
                                                   @if($rueckmeldung->answers->where('option_id', $option->id)->first() != null) checked @endif
                                                   class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500">
                                        @else
                                            <input type="checkbox"
                                                   name="answers[options][]"
                                                   value="{{$option->id}}"
                                                   @if($option->required == true) required @endif
                                                   @if($rueckmeldung->answers->where('option_id', $option->id)->first() != null) checked @endif
                                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 abfrage_edit_{{$nachricht->rueckmeldung->id}}_{{$rueckmeldung->id}}">
                                        @endif
                                        <span class="text-sm font-medium text-gray-700">{{$option->option}}</span>
                                        @if($option->required == true)
                                            <span class="ml-auto px-2 py-0.5 bg-red-100 text-red-600 text-xs font-medium rounded">Pflicht</span>
                                        @endif
                                    </label>
                                </div>
                            @elseif($option->type == 'trenner')
                                <!-- Section Divider -->
                                <div class="pt-4 pb-2 border-t-2 border-gray-300 mt-6">
                                    <h6 class="text-base font-bold text-gray-900">{{$option->option}}</h6>
                                </div>
                            @else
                                <!-- Text Input -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2 @if($option->required == true) text-red-600 @endif">
                                        {{$option->option}}
                                        @if($option->required == true)
                                            <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-600 text-xs font-medium rounded">Pflicht</span>
                                        @endif
                                    </label>
                                    @if($option->type == 'textbox')
                                        <textarea name="answers[text][{{$option->id}}]"
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                  @if($option->required == true) required @endif
                                                  rows="4">{{$rueckmeldung->answers->where('option_id', $option->id)->first()?->answer}}</textarea>
                                    @else
                                        <input name="answers[text][{{$option->id}}]"
                                               @if($option->required == true) required @endif
                                               value="{{$rueckmeldung->answers->where('option_id', $option->id)->first()?->answer}}"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                    @endif
                                </div>
                            @endif
                        @endforeach

                        <!-- Submit Button -->
                        <div class="flex gap-3">
                            <button type="submit"
                                    class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
                                    id="edit_{{$nachricht->rueckmeldung->id}}_{{$rueckmeldung->id}}_button">
                                <i class="fas fa-save mr-2"></i>
                                Änderungen speichern
                            </button>
                            <button type="button"
                                    @click="editMode = false"
                                    class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-all duration-200">
                                <i class="fas fa-times mr-2"></i>
                                Abbrechen
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @push('js')
        <script type="text/javascript">
            // Limit checkboxes for inline edit form
            var checkboxEditLimit_{{$nachricht->rueckmeldung->id}}_{{$rueckmeldung->id}} = {{($nachricht->rueckmeldung->max_answers > 0) ? $nachricht->rueckmeldung->max_answers : 100}};
            $(document).on('click', 'input.abfrage_edit_{{$nachricht->rueckmeldung->id}}_{{$rueckmeldung->id}}:checkbox', function () {
                var checkTest = $('input.abfrage_edit_{{$nachricht->rueckmeldung->id}}_{{$rueckmeldung->id}}:checked').length >= checkboxEditLimit_{{$nachricht->rueckmeldung->id}}_{{$rueckmeldung->id}};
                $('input.abfrage_edit_{{$nachricht->rueckmeldung->id}}_{{$rueckmeldung->id}}[type=checkbox]').not(":checked").attr("disabled", checkTest);
            });
        </script>
        @endpush
    @endforeach
@endif

@if($nachricht->rueckmeldung->ende->endOfDay()->greaterThan(\Carbon\Carbon::now()) and ($nachricht->rueckmeldung->multiple == true or $user->getRueckmeldung()->where('post_id', $nachricht->id)->count()==0))
    <!-- Survey Form with Confirmation Step -->
    <div id="rueckmeldeForm_{{$nachricht->id}}"
         class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden @if(!is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first())) d-none @endif"
         x-data="{
             showConfirmation: false,
             answerSummary: [],
             emptyError: false,
             options: @js($nachricht->rueckmeldung->options->toArray()),

             prepareSubmit() {
                 this.emptyError = false;
                 const form = this.$refs.abfrageForm;
                 const formData = new FormData(form);

                 this.answerSummary = [];
                 const checkedOptions = formData.getAll('answers[options][]');

                 for (const option of this.options) {
                     if (option.type === 'check') {
                         if (checkedOptions.includes(String(option.id))) {
                             this.answerSummary.push({ question: option.option, answer: 'check', type: 'check' });
                         }
                     } else if (option.type === 'text' || option.type === 'textbox') {
                         const answer = formData.get('answers[text][' + option.id + ']');
                         if (answer && answer.trim() !== '') {
                             this.answerSummary.push({ question: option.option, answer: answer, type: 'text' });
                         }
                     }
                 }

                 if (this.answerSummary.length === 0) {
                     this.emptyError = true;
                     return;
                 }

                 this.showConfirmation = true;
             },

             submitForm() {
                 this.$refs.abfrageForm.submit();
             }
         }">

        <!-- Form Header -->
        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-poll-h text-white" x-show="!showConfirmation"></i>
                    <i class="fas fa-clipboard-check text-white" x-show="showConfirmation" x-cloak></i>
                </div>
                <div>
                    <h6 class="text-white font-semibold mb-0">{{$nachricht->rueckmeldung->text}}</h6>
                    <p class="text-white/70 text-xs mb-0" x-show="!showConfirmation">Bitte beantworten Sie die folgenden Fragen</p>
                    <p class="text-white/70 text-xs mb-0" x-show="showConfirmation" x-cloak>Bitte überprüfen Sie Ihre Antworten vor dem Absenden</p>
                </div>
            </div>
        </div>

        <!-- Schritt 1: Formular ausfüllen -->
        <div class="p-4" x-show="!showConfirmation">
            <form x-ref="abfrageForm" action="{{url('userrueckmeldung/'.$nachricht->rueckmeldung->id)}}" method="POST" @submit.prevent="prepareSubmit()">
                @csrf
                <div class="space-y-4">
                    @foreach($nachricht->rueckmeldung->options as $option)
                        @if($option->type == 'check')
                            <!-- Checkbox/Radio Option -->
                            <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition-all duration-200">
                                <label class="flex items-center gap-3 w-full cursor-pointer @if($option->required == true) text-red-600 @endif">
                                    @if($nachricht->rueckmeldung->max_answers == 1)
                                        <input type="radio"
                                               name="answers[options][]"
                                               value="{{$option->id}}"
                                               @if($option->required == true) required @endif
                                               class="w-5 h-5 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    @else
                                        <input type="checkbox"
                                               name="answers[options][]"
                                               value="{{$option->id}}"
                                               @if($option->required == true) required @endif
                                               class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 abfrage_{{$nachricht->rueckmeldung->id}}">
                                    @endif
                                    <span class="text-sm font-medium text-gray-700">{{$option->option}}</span>
                                    @if($option->required == true)
                                        <span class="ml-auto px-2 py-0.5 bg-red-100 text-red-600 text-xs font-medium rounded">Pflicht</span>
                                    @endif
                                </label>
                            </div>
                        @elseif($option->type == 'trenner')
                            <!-- Section Divider -->
                            <div class="pt-4 pb-2 border-t-2 border-gray-300 mt-6">
                                <h6 class="text-base font-bold text-gray-900">{{$option->option}}</h6>
                            </div>
                        @else
                            <!-- Text Input -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2 @if($option->required == true) text-red-600 @endif">
                                    {{$option->option}}
                                    @if($option->required == true)
                                        <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-600 text-xs font-medium rounded">Pflicht</span>
                                    @endif
                                </label>
                                @if($option->type == 'textbox')
                                    <textarea name="answers[text][{{$option->id}}]"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 rueckmeldung"
                                              @if($option->required == true) required @endif
                                              rows="4"
                                              placeholder="Ihre Antwort hier eingeben..."></textarea>
                                @else
                                    <input name="answers[text][{{$option->id}}]"
                                           @if($option->required == true) required @endif
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                                           placeholder="Ihre Antwort hier eingeben...">
                                @endif
                            </div>
                        @endif
                    @endforeach

                    <!-- Fehler bei leerer Abgabe -->
                    <div x-show="emptyError" x-cloak
                         class="p-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2">
                        <i class="fas fa-exclamation-circle text-red-500 flex-shrink-0"></i>
                        <p class="text-red-600 text-sm font-medium mb-0">
                            Bitte beantworten Sie mindestens eine Frage.
                        </p>
                    </div>

                    <!-- Weiter-Button -->
                    <button type="submit"
                            class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
                            id="{{$nachricht->rueckmeldung->id}}_button">
                        <i class="fas fa-arrow-right mr-2"></i>
                        Weiter &amp; Überprüfen
                    </button>
                </div>
            </form>
        </div>

        <!-- Schritt 2: Antworten bestätigen -->
        <div class="p-4" x-show="showConfirmation" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0">

            <p class="text-sm text-gray-600 mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-indigo-500"></i>
                Bitte überprüfen Sie Ihre Antworten, bevor Sie absenden:
            </p>

            <div class="space-y-2 mb-6">
                <template x-for="(item, index) in answerSummary" :key="index">
                    <div class="flex items-start justify-between gap-4 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                        <span class="text-sm text-gray-700 flex-1 leading-snug" x-text="item.question"></span>
                        <div class="flex-shrink-0">
                            <template x-if="item.type === 'check'">
                                <div class="w-7 h-7 bg-green-500 rounded-full flex items-center justify-center">
                                    <i class="fa fa-check text-white text-xs"></i>
                                </div>
                            </template>
                            <template x-if="item.type !== 'check'">
                                <span class="inline-flex items-center px-3 py-1 bg-indigo-100 text-indigo-800 rounded-lg text-sm font-medium max-w-[12rem] text-right break-words" x-text="item.answer"></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex gap-3">
                <button type="button"
                        @click="showConfirmation = false"
                        class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-all duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Zurück
                </button>
                <button type="button"
                        @click="submitForm()"
                        class="flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Antworten absenden
                </button>
            </div>
        </div>
    </div>

    @if(!is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first()) and $nachricht->rueckmeldung->multiple == true)
        <!-- Multiple Response Button -->
        <div class="mt-4" id="rueckmeldeButton_{{$nachricht->id}}" onclick="showRueckmeldung(event,{{$nachricht->id}})">
            <button class="w-full px-4 py-3 bg-white hover:bg-indigo-50 text-indigo-600 font-medium border-2 border-indigo-500 rounded-lg transition-all duration-200">
                <i class="fas fa-plus-circle mr-2"></i>
                Weitere Rückmeldung hinzufügen
            </button>
        </div>
    @endif
@endif

@if(auth()->user()->can('edit posts') or auth()->id() == $nachricht->author)
    <!-- Admin Statistics -->
    <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden mt-6">
        <!-- Header -->
        <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-bar text-white"></i>
                    </div>
                    <h6 class="text-white font-semibold mb-0">Auswertung</h6>
                </div>
                <div class="flex items-center gap-2">
                    @if(auth()->user()->can('manage rueckmeldungen') or auth()->id() == $nachricht->author)
                        <a href="{{url('rueckmeldungen/'.$nachricht->rueckmeldung->id.'/show')}}"
                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200"
                           title="Anzeigen">
                            <i class="fa fa-eye"></i>
                        </a>
                        <a href="{{url('rueckmeldungen/'.$nachricht->rueckmeldung->id.'/download')}}"
                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200"
                           title="Download">
                            <i class="fa fa-download"></i>
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="p-4">
            <div class="grid grid-cols-1 gap-3">
                @foreach($nachricht->rueckmeldung->options()->where('type', 'check')->get() as $option)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <span class="text-sm font-medium text-gray-700 flex-1">{{$option->option}}</span>
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                                @php
                                    $total = $nachricht->userRueckmeldung->count();
                                    $percentage = $total > 0 ? ($option->answers->count() / $total * 100) : 0;
                                @endphp
                                <div class="h-full bg-indigo-600 rounded-full" style="width: {{$percentage}}%"></div>
                            </div>
                            <span class="inline-flex items-center justify-center w-10 h-10 bg-indigo-100 text-indigo-700 font-bold rounded-lg text-sm">
                                {{$option->answers->count()}}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

@push('js')
<script type="text/javascript">
    // Anzahl der auswählbaren Checkboxen begrenzen
    var checkboxLimit_{{$nachricht->rueckmeldung->id}} = {{($nachricht->rueckmeldung->max_answers > 0) ? $nachricht->rueckmeldung->max_answers : 100}};
    $('input.abfrage_{{$nachricht->rueckmeldung->id}}:checkbox').click(function () {
        var checkTest = $('input.abfrage_{{$nachricht->rueckmeldung->id}}:checked').length >= checkboxLimit_{{$nachricht->rueckmeldung->id}};
        $('input[type=checkbox][name="answers[options][]"]').not(":checked").attr("disabled", checkTest);
    });
</script>
@endpush

