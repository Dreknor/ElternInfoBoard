<div class="flex items-center justify-between mb-3">
    <div class="flex items-center gap-2">
        @php
            $moduleContact = \Illuminate\Support\Facades\Cache::get('module_contact');
            $showContactLink = $moduleContact && isset($moduleContact->options['active']) && $moduleContact->options['active'] == 1 && $nachricht->autor != null;
        @endphp

        @if($showContactLink)
            <a href="{{ url('feedback'.'/'.$nachricht->autor->id) }}" class="inline-flex items-center gap-2 text-blue-900 hover:text-blue-600 transition-colors">
                <i class="fa fa-user text-gray-500"></i>
                <span class="font-medium">{{ $nachricht->autor?->name }}</span>
            </a>
        @else
            <div class="inline-flex items-center gap-2 text-gray-700">
                <i class="fa fa-user text-gray-500"></i>
                <span class="font-medium">{{ $nachricht->autor?->name }}</span>
            </div>
        @endif
    </div>
</div>


<div class="" id="info_{{$nachricht->id}}">
    <div class="flex flex-wrap gap-2 mt-2">
        @foreach($nachricht->groups as $group)
            <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 text-xs font-xs rounded-full">
                {{ $group->name }}
            </span>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 mt-3 text-sm text-gray-600">
        <div class="flex items-center gap-2 @if($nachricht->released == 0) text-white @endif">
            <i class="far fa-clock text-gray-400"></i>
            <span>aktualisiert: {{ $nachricht->updated_at->format('d.m.Y H:i') }}</span>
        </div>
        <div class="flex items-center gap-2 @if($nachricht->released == 0) text-white @endif">
            <i class="far fa-calendar text-gray-400"></i>
            <span>Archiv ab: {{$nachricht->archiv_ab->format('d.m.Y')}}</span>
        </div>
    </div>
</div>
