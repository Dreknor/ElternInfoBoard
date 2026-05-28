<a href="{{ url('hilfe/'.$topic['slug']) }}"
   class="block p-3 rounded-lg border-2 transition-all duration-200 group
          {{ $highlight ? 'border-blue-200 bg-blue-50 hover:border-blue-400 hover:bg-blue-100' : 'border-gray-200 hover:border-blue-300 hover:bg-blue-50' }}">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                    {{ $highlight ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 group-hover:bg-blue-600 group-hover:text-white' }} transition-colors">
            <i class="{{ $topic['icon'] }} text-lg"></i>
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="font-semibold text-gray-900 mb-0.5 leading-tight">{{ $topic['title'] }}</h4>
            @if(!empty($topic['excerpt']))
                <p class="text-xs text-gray-600 mb-0 leading-snug">{{ $topic['excerpt'] }}</p>
            @endif
        </div>
        <i class="fas fa-chevron-right text-gray-400 group-hover:text-blue-600 mt-2"></i>
    </div>
</a>
