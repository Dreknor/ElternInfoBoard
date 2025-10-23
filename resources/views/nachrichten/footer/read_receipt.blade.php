@if($user->read_receipts()->where('post_id', $post->id)->first() != null)
    <!-- Read Receipt Confirmed -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-check text-white"></i>
                </div>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-green-900 mb-0">{{ __('Nachricht gelesen und bestätigt') }}</p>
                <p class="text-xs text-green-700 mb-0">
                    Bestätigt am {{ $user->read_receipts()->where('post_id', $post->id)->first()->created_at->format('d.m.Y H:i') }} Uhr
                </p>
            </div>
        </div>
    </div>
@else
    <!-- Read Receipt Not Confirmed -->
    <div class="bg-gradient-to-r from-red-50 to-orange-50 border-l-4 border-red-500 rounded-lg p-4">
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center animate-pulse">
                        <i class="fas fa-exclamation text-white"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-red-900 mb-0">{{ __('Nachricht noch nicht gelesen') }}</p>
                    <p class="text-xs text-red-700 mb-0">Bitte bestätigen Sie, dass Sie diese Nachricht gelesen haben</p>
                </div>
            </div>

            <form action="{{ route('nachrichten.read_receipt') }}" method="post">
                @csrf
                <input type="hidden" name="post_id" value="{{$post->id}}">
                <button type="submit"
                        class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ __('Nachricht als gelesen markieren') }}</span>
                </button>
            </form>
        </div>
    </div>
@endif

@if(auth()->user()->can('manage rueckmeldungen') or auth()->id() == $post->author)
    <!-- Admin Statistics -->
    <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden mt-4">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-eye text-white"></i>
                    </div>
                    <h6 class="text-white font-semibold mb-0">Lesebestätigungen</h6>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-white/20 text-white text-sm font-bold rounded-full">
                        {{ $post->receipts->count() }} / {{ $post->users->count() }}
                    </span>
                    <button onclick="document.getElementById('{{$post->id}}_receipts').classList.toggle('hidden')"
                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-list"></i>
                        <span class="hidden sm:inline">Details</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="px-4 py-3 bg-gray-50">
            @php
                $totalUsers = $post->users->count();
                $confirmedUsers = $post->receipts->count();
                $percentage = $totalUsers > 0 ? round(($confirmedUsers / $totalUsers) * 100, 1) : 0;
            @endphp

            <div class="relative">
                <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden shadow-inner">
                    <div class="h-full rounded-full transition-all duration-500 ease-out flex items-center justify-center text-xs font-bold text-white
                        @if($percentage < 30) bg-gradient-to-r from-red-500 to-red-600
                        @elseif($percentage < 70) bg-gradient-to-r from-yellow-500 to-orange-500
                        @elseif($percentage < 100) bg-gradient-to-r from-blue-500 to-blue-600
                        @else bg-gradient-to-r from-green-500 to-green-600
                        @endif"
                        style="width: {{$percentage}}%">
                        @if($percentage >= 15)
                            <span class="drop-shadow-sm">{{$percentage}}%</span>
                        @endif
                    </div>
                </div>
                @if($percentage < 15 && $percentage > 0)
                    <span class="absolute left-2 top-0.5 text-xs font-bold text-gray-600">{{$percentage}}%</span>
                @endif
            </div>

            <div class="mt-2 flex items-center justify-between text-sm">
                <span class="text-gray-600">
                    <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                    {{ $confirmedUsers }} von {{ $totalUsers }} Personen haben die Nachricht bestätigt
                </span>
                @if($confirmedUsers < $totalUsers)
                    <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded">
                        Noch {{ $totalUsers - $confirmedUsers }} ausstehend
                    </span>
                @endif
            </div>
        </div>

        <!-- Detailed Lists (Collapsible) -->
        <div class="hidden" id="{{$post->id}}_receipts">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-4">
                <!-- Confirmed List -->
                <div class="bg-white rounded-lg border-2 border-green-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-4 py-2">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-white"></i>
                            <h6 class="text-sm font-semibold text-white mb-0">{{ __('Bestätigt') }} ({{ $post->receipts->count() }})</h6>
                        </div>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        @if($post->receipts->count() > 0)
                            <div class="divide-y divide-gray-200">
                                @foreach($post->receipts->sortByDesc('created_at') as $receipt)
                                    @if(!is_null($receipt->user))
                                        <div class="flex items-center justify-between px-4 py-3 hover:bg-green-50 transition-colors duration-150">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <span class="text-white font-bold text-xs">
                                                        {{substr($receipt->user->name, 0, 1)}}
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 mb-0">{{ $receipt->user->name }}</p>
                                                    <p class="text-xs text-gray-500 mb-0">
                                                        <i class="far fa-clock mr-1"></i>
                                                        {{ $receipt->created_at->format('d.m.Y H:i') }} Uhr
                                                    </p>
                                                </div>
                                            </div>
                                            <i class="fas fa-check text-green-500"></i>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="p-8 text-center">
                                <i class="fas fa-inbox text-gray-300 text-3xl mb-2"></i>
                                <p class="text-sm text-gray-500">Noch keine Bestätigungen</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Not Confirmed List -->
                <div class="bg-white rounded-lg border-2 border-orange-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-2">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-clock text-white"></i>
                            <h6 class="text-sm font-semibold text-white mb-0">{{ __('Nicht bestätigt') }} ({{ $post->users->count() - $post->receipts->count() }})</h6>
                        </div>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        @php
                            $unconfirmedUsers = $post->users->filter(function($user) use ($post) {
                                return !is_null($user) && $post->receipts->where('user_id', $user->id)->first() == null;
                            });
                        @endphp

                        @if($unconfirmedUsers->count() > 0)
                            <div class="divide-y divide-gray-200">
                                @foreach($unconfirmedUsers as $user)
                                    <div class="flex items-center justify-between px-4 py-3 hover:bg-orange-50 transition-colors duration-150">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full flex items-center justify-center flex-shrink-0">
                                                <span class="text-white font-bold text-xs">
                                                    {{substr($user->name, 0, 1)}}
                                                </span>
                                            </div>
                                            <p class="text-sm font-medium text-gray-900 mb-0">{{ $user->name }}</p>
                                        </div>
                                        <i class="fas fa-hourglass-half text-orange-500"></i>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-8 text-center">
                                <i class="fas fa-check-double text-green-400 text-3xl mb-2"></i>
                                <p class="text-sm text-gray-600 font-medium">Alle haben bestätigt! 🎉</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

