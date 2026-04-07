@php
    $receipt = $user->read_receipts()->where('post_id', $post->id)->first();
    $isConfirmed = $receipt && $receipt->confirmed_at;
@endphp

@if($isConfirmed)
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
                    Bestätigt am {{ $receipt->confirmed_at->format('d.m.Y H:i') }} Uhr
                </p>
            </div>
        </div>
    </div>
@else
    @php
        $wasReminded = $receipt && $receipt->reminded_at && !$receipt->confirmed_at;
    @endphp
    <!-- Read Receipt Not Confirmed / Reminded -->
    <div class="bg-gradient-to-r {{ $wasReminded ? 'from-yellow-50 to-amber-50 border-yellow-500' : 'from-red-50 to-orange-50 border-red-500' }} border-l-4 rounded-lg p-4">
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 {{ $wasReminded ? 'bg-yellow-500' : 'bg-red-500' }} rounded-full flex items-center justify-center {{ $wasReminded ? '' : 'animate-pulse' }}">
                        <i class="fas {{ $wasReminded ? 'fa-bell' : 'fa-exclamation' }} text-white"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold {{ $wasReminded ? 'text-yellow-900' : 'text-red-900' }} mb-0">
                        {{ $wasReminded ? __('Erinnerung versendet') : __('Nachricht noch nicht gelesen') }}
                    </p>
                    <p class="text-xs {{ $wasReminded ? 'text-yellow-700' : 'text-red-700' }} mb-0">
                        {{ $wasReminded ? 'Sie wurden bereits erinnert, die Lesebestätigung fehlt weiterhin.' : 'Bitte bestätigen Sie, dass Sie diese Nachricht gelesen haben' }}
                    </p>
                </div>
            </div>

            <form action="{{ route('nachrichten.read_receipt') }}" method="post">
                @csrf
                <input type="hidden" name="post_id" value="{{$post->id}}">
                <button type="submit"
                        class="w-full px-6 py-3 {{ $wasReminded ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-red-600 hover:bg-red-700' }} text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ __('Nachricht als gelesen markieren') }}</span>
                </button>
            </form>
        </div>
    </div>
@endif

@if(auth()->user()->can('manage rueckmeldungen') or auth()->id() == $post->author)
    @php
        // Build buckets - Filter unique users to avoid duplicates from multiple groups
        $allUsers = $post->users->filter(fn($u) => !is_null($u))->unique('id');
        $receipts = $post->receipts->keyBy('user_id');
        $confirmed = collect();
        $reminded = collect();
        $pending = collect();
        foreach ($allUsers as $u) {
            $r = $receipts->get($u->id);
            if ($r && $r->confirmed_at) {
                $confirmed->push(['user' => $u, 'receipt' => $r]);
            } elseif ($r && $r->reminded_at) {
                $reminded->push(['user' => $u, 'receipt' => $r]);
            } else {
                $pending->push($u);
            }
        }
        $totalUsers = $allUsers->count();
        $confirmedUsers = $confirmed->count();
        $remindedUsers = $reminded->count();
        $percentage = $totalUsers > 0 ? round(($confirmedUsers / $totalUsers) * 100, 1) : 0;
    @endphp
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
                        {{ $confirmedUsers }} bestätigt • {{ $remindedUsers }} erinnert • {{ $totalUsers }} gesamt
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
            <div class="relative">
                <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden shadow-inner flex">
                    <div class="h-full bg-gradient-to-r from-green-500 to-green-600" style="width: {{ $percentage }}%"></div>
                    @php $remindedPercent = $totalUsers > 0 ? round(($remindedUsers / $totalUsers) * 100, 1) : 0; @endphp
                    <div class="h-full bg-gradient-to-r from-yellow-400 to-amber-500" style="width: {{ $remindedPercent }}%"></div>
                </div>
                <div class="absolute inset-0 flex items-center justify-center text-xs font-bold text-gray-700">
                    {{ $percentage }}% bestätigt, {{ $remindedUsers }} erinnert
                </div>
            </div>

            <div class="mt-2 flex items-center justify-between text-sm">
                <span class="text-gray-600">
                    <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                    {{ $confirmedUsers }} von {{ $totalUsers }} Personen haben die Nachricht bestätigt
                </span>
                @if($confirmedUsers < $totalUsers)
                    <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded">
                        Noch {{ $totalUsers - $confirmedUsers }} ausstehend ({{ $remindedUsers }} erinnert)
                    </span>
                @endif
            </div>
        </div>

        <!-- Detailed Lists (Collapsible) -->
        <div class="hidden" id="{{$post->id}}_receipts">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4">
                <!-- Confirmed List -->
                <div class="bg-white rounded-lg border-2 border-green-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-4 py-2">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-white"></i>
                            <h6 class="text-sm font-semibold text-white mb-0">{{ __('Bestätigt') }} (<span class="confirmed-count">{{ $confirmedUsers }}</span>)</h6>
                        </div>
                    </div>
                    @if($confirmedUsers > 0)
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                        <input type="text"
                               class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Suchen..."
                               onkeyup="filterUsers(this, 'confirmed-list-{{ $post->id }}', 'confirmed-count')">
                    </div>
                    @endif
                    <div class="max-h-96 overflow-y-auto" id="confirmed-list-{{ $post->id }}">
                        @if($confirmedUsers > 0)
                            <div class="divide-y divide-gray-200">
                                @foreach($confirmed->sortByDesc(fn($r)=>$r['receipt']->confirmed_at) as $entry)
                                    @php $receipt = $entry['receipt']; $u = $entry['user']; @endphp
                                    <div class="flex items-center justify-between px-4 py-3 hover:bg-green-50 transition-colors duration-150 user-item" data-name="{{ strtolower($u->name) }}">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                                                <span class="text-white font-bold text-xs">{{ substr($u->name, 0, 1) }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 mb-0">{{ $u->name }}</p>
                                                <p class="text-xs text-gray-500 mb-0"><i class="far fa-clock mr-1"></i>{{ $receipt->confirmed_at->format('d.m.Y H:i') }} Uhr</p>
                                            </div>
                                        </div>
                                        <i class="fas fa-check text-green-500"></i>
                                    </div>
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

                <!-- Reminded List -->
                <div class="bg-white rounded-lg border-2 border-yellow-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-yellow-500 to-amber-500 px-4 py-2">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-bell text-white"></i>
                            <h6 class="text-sm font-semibold text-white mb-0">{{ __('Erinnert') }} (<span class="reminded-count">{{ $remindedUsers }}</span>)</h6>
                        </div>
                    </div>
                    @if($remindedUsers > 0)
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                        <input type="text"
                               class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                               placeholder="Suchen..."
                               onkeyup="filterUsers(this, 'reminded-list-{{ $post->id }}', 'reminded-count')">
                    </div>
                    @endif
                    <div class="max-h-96 overflow-y-auto" id="reminded-list-{{ $post->id }}">
                        @if($remindedUsers > 0)
                            <div class="divide-y divide-gray-200">
                                @foreach($reminded->sortByDesc(fn($r)=>$r['receipt']->reminded_at) as $entry)
                                    @php $receipt = $entry['receipt']; $u = $entry['user']; @endphp
                                    <div class="flex items-center justify-between px-4 py-3 hover:bg-yellow-50 transition-colors duration-150 user-item" data-user-id="{{ $u->id }}" data-name="{{ strtolower($u->name) }}">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-gradient-to-br from-yellow-500 to-amber-500 rounded-full flex items-center justify-center flex-shrink-0">
                                                <span class="text-white font-bold text-xs">{{ substr($u->name, 0, 1) }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 mb-0">{{ $u->name }}</p>
                                                <p class="text-xs text-gray-500 mb-0"><i class="far fa-clock mr-1"></i>{{ $receipt->reminded_at?->format('d.m.Y H:i') }} Uhr</p>
                                            </div>
                                        </div>
                                        <button
                                            onclick="confirmReadReceipt({{ $post->id }}, {{ $u->id }}, '{{ $u->name }}')"
                                            class="ml-2 px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition-colors duration-150"
                                            title="Als gelesen bestätigen">
                                            <i class="fas fa-check mr-1"></i>Bestätigen
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-8 text-center">
                                <i class="fas fa-bell-slash text-gray-300 text-3xl mb-2"></i>
                                <p class="text-sm text-gray-500">Niemand wurde erinnert</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Not Confirmed List -->
                <div class="bg-white rounded-lg border-2 border-orange-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-2">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-clock text-white"></i>
                            <h6 class="text-sm font-semibold text-white mb-0">{{ __('Nicht bestätigt') }} (<span class="pending-count">{{ $pending->count() }}</span>)</h6>
                        </div>
                    </div>
                    @if($pending->count() > 0)
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                        <input type="text"
                               class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Suchen..."
                               onkeyup="filterUsers(this, 'pending-list-{{ $post->id }}', 'pending-count')">
                    </div>
                    @endif
                    <div class="max-h-96 overflow-y-auto" id="pending-list-{{ $post->id }}">
                        @if($pending->count() > 0)
                            <div class="divide-y divide-gray-200">
                                @foreach($pending as $u)
                                    <div class="flex items-center justify-between px-4 py-3 hover:bg-orange-50 transition-colors duration-150 user-item" data-user-id="{{ $u->id }}" data-name="{{ strtolower($u->name) }}">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full flex items-center justify-center flex-shrink-0">
                                                <span class="text-white font-bold text-xs">
                                                    {{substr($u->name, 0, 1)}}
                                                </span>
                                            </div>
                                            <p class="text-sm font-medium text-gray-900 mb-0">{{ $u->name }}</p>
                                        </div>
                                        <button
                                            onclick="confirmReadReceipt({{ $post->id }}, {{ $u->id }}, '{{ $u->name }}')"
                                            class="ml-2 px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition-colors duration-150"
                                            title="Als gelesen bestätigen">
                                            <i class="fas fa-check mr-1"></i>Bestätigen
                                        </button>
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

    <script>
    function filterUsers(input, listId, countClass) {
        const searchTerm = input.value.toLowerCase();
        const listContainer = document.getElementById(listId);
        const userItems = listContainer.querySelectorAll('.user-item');
        let visibleCount = 0;

        userItems.forEach(item => {
            const userName = item.getAttribute('data-name');
            if (userName.includes(searchTerm)) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Update count in header
        const countSpan = document.querySelector('.' + countClass);
        if (countSpan) {
            countSpan.textContent = visibleCount;
        }
    }

    function confirmReadReceipt(postId, userId, userName) {
        if (!confirm(`Lesebestätigung für ${userName} manuell bestätigen?`)) {
            return;
        }

        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Lädt...';

        fetch(`/post/${postId}/readReceipt/${userId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to update lists
                location.reload();
            } else {
                alert('Fehler beim Speichern der Lesebestätigung');
                button.disabled = false;
                button.innerHTML = originalContent;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Fehler beim Speichern der Lesebestätigung');
            button.disabled = false;
            button.innerHTML = originalContent;
        });
    }
    </script>
@endif
