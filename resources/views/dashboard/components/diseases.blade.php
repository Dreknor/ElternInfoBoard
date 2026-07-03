{{-- Dashboard-Widget: Meldepflichtige Erkrankungen --}}
@canany(['manage diseases', 'see diseases'])
    @if($dashboardDiseasesWidget !== null)
    <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
            <!-- Header -->
            <div class="px-4 py-3 border-b d-flex justify-content-between align-items-center"
                 style="background: linear-gradient(to right, var(--color-widget-warning-from), var(--color-widget-warning-to)); border-color: var(--color-widget-warning-border);">
                <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
                    <i class="fas fa-virus"></i>
                    Meldepflichtige Erkrankungen
                    @can('manage diseases')
                        <span class="ml-2 text-xs font-normal px-2 py-0.5 rounded-full"
                              style="background: rgba(255,255,255,0.2); color: var(--color-widget-header-text);">
                            inkl. unveröffentlichte
                        </span>
                    @endcan
                </h5>
                @can('manage diseases')
                    <a href="{{ route('diseases.index') }}"
                       class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium rounded-lg transition-colors duration-200 text-decoration-none"
                       style="background: rgba(255,255,255,0.15); color: var(--color-widget-header-text); border: 1px solid rgba(255,255,255,0.3);"
                       onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                        <i class="fas fa-cog text-xs"></i> Verwalten
                    </a>
                @endcan
            </div>

            <!-- Body -->
            <div class="p-4" style="background: var(--color-widget-body-bg);">
                @if(!$dashboardDiseasesWidget->isEmpty())
                    <div class="space-y-2">
                        @foreach($dashboardDiseasesWidget as $disease)
                            <div class="p-3 rounded-lg"
                                 style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);
                                        {{ !$disease->active ? 'opacity: 0.65;' : '' }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <!-- Name & Zeitraum -->
                                    <div class="flex-1">
                                        <span class="font-semibold d-flex align-items-center gap-2"
                                              style="color: var(--color-text-primary);">
                                            <i class="fas fa-disease text-sm" style="color: var(--color-widget-warning-from);"></i>
                                            {{ $disease->disease->name }}
                                        </span>
                                        <div class="text-xs mt-1" style="color: var(--color-text-secondary);">
                                            <i class="far fa-calendar mr-1"></i>
                                            {{ $disease->start->format('d.m.Y') }}
                                            @if($disease->end)
                                                &ndash; {{ $disease->end->format('d.m.Y') }}
                                            @endif
                                        </div>
                                    </div>
                                    <!-- Status-Badge (nur für manage diseases) -->
                                    @can('manage diseases')
                                        <div class="flex-shrink-0 ml-3">
                                            @if($disease->active)
                                                <span class="badge badge-danger badge-pill d-inline-flex align-items-center gap-1">
                                                    <i class="fas fa-circle" style="font-size: 6px;"></i>
                                                    Veröffentlicht
                                                </span>
                                            @else
                                                <span class="badge badge-secondary badge-pill d-inline-flex align-items-center gap-1">
                                                    <i class="fas fa-eye-slash" style="font-size: 8px;"></i>
                                                    Unveröffentlicht
                                                </span>
                                            @endif
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs mt-3 mb-0 d-flex align-items-center gap-1"
                       style="color: var(--color-text-muted);">
                        <i class="fas fa-info-circle"></i>
                        Bitte beachten Sie die entsprechenden Hygienemaßnahmen.
                    </p>
                @endif
            </div>
        </div>
    @endif
@endcanany
