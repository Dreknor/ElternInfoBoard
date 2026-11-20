<a href="{{ url('hilfe/'.$topic['slug']) }}"
   class="help-topic-card {{ $highlight ? 'help-topic-card-highlight' : '' }} block p-3 rounded-xl group">
    <div class="flex items-start gap-3">
        <div class="help-topic-icon w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors">
            <i class="{{ $topic['icon'] }} text-lg" aria-hidden="true"></i>
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="font-semibold ea-text text-sm mb-0.5 leading-tight">{{ $topic['title'] }}</h4>
            @if(!empty($topic['excerpt']))
                <p class="text-xs ea-muted mb-0 leading-snug">{{ $topic['excerpt'] }}</p>
            @endif
        </div>
        <i class="help-topic-chevron fas fa-chevron-right mt-2" aria-hidden="true"></i>
    </div>
</a>
