{{-- Beitrag melden - Button + Modal --}}
@auth
    @if(auth()->id() !== $nachricht->author)
        <div class="bg-white px-4 py-2 flex justify-end" id="report-section-{{$nachricht->id}}">
            <button type="button"
                    onclick="document.getElementById('postReportModal-{{$nachricht->id}}').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors duration-200"
                    title="Beitrag melden">
                <i class="fas fa-flag"></i>
                <span>Melden</span>
            </button>
        </div>

        {{-- Modal: Beitrag melden --}}
        <div id="postReportModal-{{$nachricht->id}}"
             class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="font-bold text-gray-800">
                        <i class="fas fa-flag mr-2 text-orange-500"></i>Beitrag melden
                    </h3>
                    <button onclick="document.getElementById('postReportModal-{{$nachricht->id}}').classList.add('hidden')"
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="{{ route('post.report', $nachricht) }}" method="POST" class="p-4">
                    @csrf
                    <p class="text-sm text-gray-600 mb-3">
                        Beitrag: <strong>{{ $nachricht->header }}</strong>
                    </p>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Grund der Meldung:</label>
                    <textarea name="reason" rows="3" required maxlength="500"
                              placeholder="Beschreibe kurz, warum du diesen Beitrag meldest..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 outline-none resize-none text-sm mb-3"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button"
                                onclick="document.getElementById('postReportModal-{{$nachricht->id}}').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">
                            Abbrechen
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm transition-colors">
                            <i class="fas fa-flag mr-1"></i> Melden
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endauth

