@canany(['manage diseases', 'see disease'])
    @if($dashboardDiseasesWidget !== null)
        <div class="col-12 mb-4">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="px-4 py-3 border-b d-flex justify-content-between align-items-center"
                     style="background: linear-gradient(to right, #dc2626, #b91c1c);">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="fas fa-virus"></i>
                        Meldepflichtige Erkrankungen
                        @can('manage diseases')
                            <span class="ml-2 text-xs font-normal bg-white bg-opacity-20 px-2 py-0.5 rounded-full">
                                inkl. unveröffentlichte
                            </span>
                        @endcan
                    </h5>
                    @can('manage diseases')
                        <a href="{{ route('diseases.index') }}"
                           class="inline-flex items-center gap-1 px-3 py-1 bg-white bg-opacity-10 hover:bg-opacity-20
                                  text-white text-sm font-medium rounded-lg border border-white border-opacity-30
                                  transition-colors duration-200 text-decoration-none">
                            <i class="fas fa-cog text-xs"></i> Verwalten
                        </a>
                    @endcan
                </div>

                <div class="p-4">
                    @if($dashboardDiseasesWidget->isEmpty())
                        <div class="text-center py-6">
                            <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                            <p class="text-gray-500 mb-0">Keine aktuellen Erkrankungen vorhanden</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-2 pr-4 font-semibold text-gray-700">Erkrankung</th>
                                        <th class="text-left py-2 pr-4 font-semibold text-gray-700">Von</th>
                                        <th class="text-left py-2 pr-4 font-semibold text-gray-700">Bis</th>
                                        @can('manage diseases')
                                            <th class="text-left py-2 font-semibold text-gray-700">Status</th>
                                        @endcan
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($dashboardDiseasesWidget as $disease)
                                        <tr class="hover:bg-gray-50 transition-colors duration-100
                                                   @can('manage diseases') {{ !$disease->active ? 'opacity-60' : '' }} @endcan">
                                            <td class="py-2 pr-4">
                                                <span class="font-medium text-gray-800 flex items-center gap-2">
                                                    <i class="fas fa-disease text-red-400 text-xs"></i>
                                                    {{ $disease->disease->name }}
                                                </span>
                                            </td>
                                            <td class="py-2 pr-4 text-gray-600">
                                                <i class="far fa-calendar text-gray-400 mr-1"></i>
                                                {{ $disease->start->format('d.m.Y') }}
                                            </td>
                                            <td class="py-2 pr-4 text-gray-600">
                                                @if($disease->end)
                                                    <i class="far fa-calendar-check text-gray-400 mr-1"></i>
                                                    {{ $disease->end->format('d.m.Y') }}
                                                @else
                                                    <span class="text-gray-400">–</span>
                                                @endif
                                            </td>
                                            @can('manage diseases')
                                                <td class="py-2">
                                                    @if($disease->active)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5
                                                                     bg-red-100 text-red-700 text-xs font-semibold rounded-full">
                                                            <i class="fas fa-circle text-[6px] animate-pulse"></i>
                                                            Veröffentlicht
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5
                                                                     bg-gray-100 text-gray-500 text-xs font-semibold rounded-full">
                                                            <i class="fas fa-eye-slash text-[8px]"></i>
                                                            Unveröffentlicht
                                                        </span>
                                                    @endif
                                                </td>
                                            @endcan
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-500 mt-3 mb-0">
                            <i class="fas fa-info-circle"></i>
                            Bitte beachten Sie die entsprechenden Hygienemaßnahmen.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endcanany

