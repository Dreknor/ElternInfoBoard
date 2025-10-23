@if($nachricht->reactable != 0)
    <div class="bg-white px-4 py-3" id="reactions-{{$nachricht->id}}">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-heart text-gray-400"></i>
                <span class="text-sm text-gray-600" id="reaction-count-{{$nachricht->id}}">
                    @if($nachricht->reactions->count() > 0)
                        <span class="font-semibold text-gray-900">{{$nachricht->reactions->count()}}</span> Reaktion(en)
                    @else
                        Noch keine Reaktionen
                    @endif
                </span>
            </div>

            <!-- Reaction Dropdown -->
            <div class="relative inline-block" x-data="{ open: false }">
                <button type="button"
                       @click="open = !open"
                       @click.away="open = false"
                       id="reaction-button-{{$nachricht->id}}"
                       class="inline-flex items-center gap-2 px-4 py-2 @if($nachricht->reacted()) bg-blue-100 text-blue-700 hover:bg-blue-200 @else bg-gray-100 text-gray-700 hover:bg-gray-200 @endif font-medium rounded-lg transition-all duration-200 shadow-sm">
                    <i class="far fa-thumbs-up text-lg"></i>
                    <span class="text-sm" id="reaction-button-text-{{$nachricht->id}}">@if($nachricht->reacted()) Reagiert @else Reagieren @endif</span>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 bottom-full mb-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50"
                     style="display: none;">

                    <a href="#"
                       onclick="reactToPost({{$nachricht->id}}, 'like', event)"
                       class="flex items-center justify-between px-4 py-2 hover:bg-blue-50 transition-colors duration-150">
                        <div class="flex items-center gap-2">
                            <i class="far fa-thumbs-up text-blue-500 text-xl"></i>
                            <span class="text-sm font-medium text-gray-700">Gefällt mir</span>
                        </div>
                        <span class="px-2 py-0.5 bg-gray-200 text-gray-700 text-xs font-medium rounded-full" id="reaction-like-{{$nachricht->id}}">
                            @if($nachricht->reactions->count() > 0)
                                {{round(($nachricht->reactions->where('name', 'like')->count()/$nachricht->reactions->count())*100)}}%
                            @else
                                0%
                            @endif
                        </span>
                    </a>

                    <a href="#"
                       onclick="reactToPost({{$nachricht->id}}, 'love', event)"
                       class="flex items-center justify-between px-4 py-2 hover:bg-red-50 transition-colors duration-150">
                        <div class="flex items-center gap-2">
                            <i class="far fa-heart text-red-500 text-xl"></i>
                            <span class="text-sm font-medium text-gray-700">Liebe es</span>
                        </div>
                        <span class="px-2 py-0.5 bg-gray-200 text-gray-700 text-xs font-medium rounded-full" id="reaction-love-{{$nachricht->id}}">
                            @if($nachricht->reactions->count() > 0)
                                {{round(($nachricht->reactions->where('name', 'love')->count()/$nachricht->reactions->count())*100)}}%
                            @else
                                0%
                            @endif
                        </span>
                    </a>

                    <a href="#"
                       onclick="reactToPost({{$nachricht->id}}, 'haha', event)"
                       class="flex items-center justify-between px-4 py-2 hover:bg-yellow-50 transition-colors duration-150">
                        <div class="flex items-center gap-2">
                            <i class="far fa-laugh-squint text-yellow-500 text-xl"></i>
                            <span class="text-sm font-medium text-gray-700">Haha</span>
                        </div>
                        <span class="px-2 py-0.5 bg-gray-200 text-gray-700 text-xs font-medium rounded-full" id="reaction-haha-{{$nachricht->id}}">
                            @if($nachricht->reactions->count() > 0)
                                {{round(($nachricht->reactions->where('name', 'haha')->count()/$nachricht->reactions->count())*100)}}%
                            @else
                                0%
                            @endif
                        </span>
                    </a>

                    <a href="#"
                       onclick="reactToPost({{$nachricht->id}}, 'wow', event)"
                       class="flex items-center justify-between px-4 py-2 hover:bg-orange-50 transition-colors duration-150">
                        <div class="flex items-center gap-2">
                            <i class="far fa-surprise text-orange-500 text-xl"></i>
                            <span class="text-sm font-medium text-gray-700">Wow</span>
                        </div>
                        <span class="px-2 py-0.5 bg-gray-200 text-gray-700 text-xs font-medium rounded-full" id="reaction-wow-{{$nachricht->id}}">
                            @if($nachricht->reactions->count() > 0)
                                {{round(($nachricht->reactions->where('name', 'wow')->count()/$nachricht->reactions->count())*100)}}%
                            @else
                                0%
                            @endif
                        </span>
                    </a>

                    <a href="#"
                       onclick="reactToPost({{$nachricht->id}}, 'sad', event)"
                       class="flex items-center justify-between px-4 py-2 hover:bg-gray-50 transition-colors duration-150">
                        <div class="flex items-center gap-2">
                            <i class="far fa-sad-tear text-gray-500 text-xl"></i>
                            <span class="text-sm font-medium text-gray-700">Traurig</span>
                        </div>
                        <span class="px-2 py-0.5 bg-gray-200 text-gray-700 text-xs font-medium rounded-full" id="reaction-sad-{{$nachricht->id}}">
                            @if($nachricht->reactions->count() > 0)
                                {{round(($nachricht->reactions->where('name', 'sad')->count()/$nachricht->reactions->count())*100)}}%
                            @else
                                0%
                            @endif
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('js')
    <script>
        function reactToPost(postId, reactionType, event) {
            event.preventDefault();

            // AJAX request to react
            $.ajax({
                url: '/posts/' + postId + '/react/' + reactionType,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.success) {
                        // Update total count
                        const countElement = $('#reaction-count-' + postId);
                        if (response.total_reactions > 0) {
                            countElement.html('<span class="font-semibold text-gray-900">' + response.total_reactions + '</span> Reaktion(en)');
                        } else {
                            countElement.html('Noch keine Reaktionen');
                        }

                        // Update button state
                        const button = $('#reaction-button-' + postId);
                        const buttonText = $('#reaction-button-text-' + postId);

                        if (response.user_reacted) {
                            button.removeClass('bg-gray-100 text-gray-700 hover:bg-gray-200');
                            button.addClass('bg-blue-100 text-blue-700 hover:bg-blue-200');
                            buttonText.text('Reagiert');
                        } else {
                            button.removeClass('bg-blue-100 text-blue-700 hover:bg-blue-200');
                            button.addClass('bg-gray-100 text-gray-700 hover:bg-gray-200');
                            buttonText.text('Reagieren');
                        }

                        // Update all percentages
                        $('#reaction-like-' + postId).text(response.reactions_breakdown.like.percentage + '%');
                        $('#reaction-love-' + postId).text(response.reactions_breakdown.love.percentage + '%');
                        $('#reaction-haha-' + postId).text(response.reactions_breakdown.haha.percentage + '%');
                        $('#reaction-wow-' + postId).text(response.reactions_breakdown.wow.percentage + '%');
                        $('#reaction-sad-' + postId).text(response.reactions_breakdown.sad.percentage + '%');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error reacting to post:', error);
                    // Fallback to redirect if AJAX fails
                    window.location.href = '/posts/' + postId + '/react/' + reactionType;
                }
            });
        }
    </script>
    @endpush
@endif

