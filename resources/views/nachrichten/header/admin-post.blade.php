@if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'abfrage')
    <a href="{{url('rueckmeldungen/'.$nachricht->rueckmeldung->id."/download")}}"
       title="Download"
       class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors duration-200">
        <i class="fa fa-download"></i>
    </a>
@endif

<div class="relative inline-block" x-data="{ open: false }">
    <button type="button"
            @click="open = !open"
            @click.away="open = false"
            class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
        <i class="fa fa-ellipsis-v"></i>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute left-0 md:left-auto md:right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-50"
         style="display: none;">

        <a class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
           href="{{url('/posts/edit/'.$nachricht->id)}}">
            <i class="far fa-edit text-amber-500"></i>
            <span>Bearbeiten</span>
        </a>

        @if($nachricht->updated_at->greaterThan(\Carbon\Carbon::now()->subWeeks(3)) or $nachricht->archiv_ab->greaterThan(\Carbon\Carbon::now()))
            <a href="{{url('/posts/touch/'.$nachricht->id)}}"
               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                <i class="fas fa-redo text-blue-500"></i>
                <span>Aktualisieren</span>
            </a>
        @else
            <a href="{{url('/posts/touch/'.$nachricht->id)}}"
               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                <i class="far fa-clone text-gray-500"></i>
                <span>Kopieren</span>
            </a>
        @endif

        @if($nachricht->released == 0 and auth()->user()->can('release posts'))
            <a class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
               href="{{url('/posts/release/'.$nachricht->id)}}">
                <i class="far fa-eye text-green-500"></i>
                <span>Veröffentlichen</span>
            </a>
        @endif

        @if($nachricht->released == 1 and !$nachricht->is_archived)
            <a href="{{url('/posts/archiv/'.$nachricht->id)}}"
               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                <i class="fas fa-archive text-orange-500"></i>
                <span>Archivieren</span>
            </a>
        @endif

        @if(auth()->user()->can('make sticky'))
            <a href="{{url('/posts/stick/'.$nachricht->id)}}"
               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                <i class="fas fa-thumbtack @if($nachricht->sticky) text-yellow-500 @else text-purple-500 @endif"
                   @if($nachricht->sticky) style="transform: rotate(45deg)" @endif></i>
                <span>@if(!$nachricht->sticky) Anheften @else Lösen @endif</span>
            </a>
        @endif

        @if($nachricht->released != 1 and (auth()->user()->can('delete posts') or auth()->id() == $nachricht->author))
            <div class="border-t border-gray-200 my-1"></div>
            <a href="{{url('/posts/delete/'.$nachricht->id)}}"
               class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                <i class="fas fa-trash"></i>
                <span>Löschen</span>
            </a>
        @endif
    </div>
</div>

