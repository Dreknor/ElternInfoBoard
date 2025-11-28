@if(auth()->check() && auth()->user()->can('release posts'))
    <div class="mt-2 px-3 py-1.5 text-xs font-semibold tracking-wide @if($nachricht->published_wp_id) text-green-700 bg-green-50 border border-green-200 @else text-amber-700 bg-amber-50 border border-amber-200 @endif rounded flex items-center gap-2">
        @if($nachricht->published_wp_id)
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
            <span>Homepage veröffentlicht</span>
            <a href="https://{{ config('wordpress.wp_url') }}/?p={{$nachricht->published_wp_id}}" target="_blank" class="underline text-green-700 hover:text-green-900">ansehen</a>
        @endif
    </div>
@endif

