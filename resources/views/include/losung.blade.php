@if($losung)
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg shadow-lg overflow-hidden mb-2">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2">
            <div class="flex items-center space-x-2">
                <div class="bg-white/20 rounded p-1.5">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-white leading-tight">Tageslosung zum {{\Carbon\Carbon::parse($losung->date)->format('d.m.Y')}}</h5>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200">
            <!-- Losung -->
            <div class="p-3 bg-white">
                <div class="flex items-start space-x-2">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h6 class="text-xs font-semibold text-gray-700 mb-1">Losung</h6>
                        <p class="text-gray-900 text-sm leading-snug mb-1.5">
                            {{$losung?->Losungstext}}
                        </p>
                        <p class="text-xs text-gray-600 italic flex items-center">
                            <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                            {{$losung?->Losungsvers}}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Lehrtext -->
            <div class="p-3 bg-gradient-to-br from-white to-indigo-50/30">
                <div class="flex items-start space-x-2">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h6 class="text-xs font-semibold text-gray-700 mb-1">Lehrtext</h6>
                        <p class="text-gray-900 text-sm leading-snug mb-1.5">
                            {{$losung?->Lehrtext}}
                        </p>
                        <p class="text-xs text-gray-600 italic flex items-center">
                            <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                            {{$losung?->Lehrtextvers}}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
