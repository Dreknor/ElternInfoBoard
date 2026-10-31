{{-- Kopf und Reiter der Verwaltung „Elternabfragen“. Optional: $title, $subtitle, $icon, $back (['url' => …, 'label' => …]) --}}
@php
    $user = auth()->user();
    $tabs = array_filter([
        'elternabfragen.admin.index' => $user->canAny(['manage elternabfragen', 'approve elternabfragen', 'view elternabfragen answers', 'export elternabfragen'])
            ? ['label' => 'Abfragen & Rücklauf', 'icon' => 'fas fa-tasks'] : null,
        'elternabfragen.admin.templates.index' => $user->canAny(['manage elternabfragen', 'approve elternabfragen'])
            ? ['label' => 'Vorlagen', 'icon' => 'fas fa-layer-group'] : null,
        'elternabfragen.admin.approvals' => $user->can('approve elternabfragen')
            ? ['label' => 'Freigaben', 'icon' => 'fas fa-user-check', 'count' => $pendingApprovals ?? 0] : null,
        'elternabfragen.admin.staff' => $user->canAny(['read elternabfragen', 'view elternabfragen answers'])
            ? ['label' => 'Antworten', 'icon' => 'fas fa-eye'] : null,
        'elternabfragen.admin.settings' => $user->canAny(['approve elternabfragen', 'edit settings'])
            ? ['label' => 'Regeln', 'icon' => 'fas fa-sliders-h'] : null,
    ]);
    $current = request()->route()?->getName();
@endphp
<div class="mb-6">
    @isset($back)
        <a href="{{ $back['url'] }}" class="inline-flex items-center gap-2 mb-3 text-sm ea-link">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> {{ $back['label'] }}
        </a>
    @endisset
    <div class="ea-hero">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
                <div class="ea-hero-icon"><i class="{{ $icon ?? 'fas fa-clipboard-check' }}" aria-hidden="true"></i></div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider mb-0.5" style="color: rgba(255,255,255,.7)">Elternabfragen</p>
                    <h1 class="ea-hero-title">{{ $title ?? 'Elternabfragen' }}</h1>
                    @isset($subtitle)
                        <p class="ea-hero-sub">{{ $subtitle }}</p>
                    @endisset
                </div>
            </div>
            @hasSection('ea-actions')
                {{-- Aktionen über @section('ea-actions') vor @section('content') --}}
                <div class="flex flex-wrap items-center gap-2">@yield('ea-actions')</div>
            @endif
        </div>
    </div>
    @if(count($tabs) > 1)
        <nav class="ea-tabs" aria-label="Bereiche">
            @foreach($tabs as $route => $tab)
                @php
                    $prefixes = match ($route) {
                        'elternabfragen.admin.index' => ['elternabfragen.admin.index', 'elternabfragen.admin.inquiries.', 'elternabfragen.admin.assignments.', 'elternabfragen.admin.exports.'],
                        'elternabfragen.admin.templates.index' => ['elternabfragen.admin.templates.'],
                        'elternabfragen.admin.staff' => ['elternabfragen.admin.staff', 'elternabfragen.admin.responses.'],
                        default => [$route],
                    };
                    $active = \Illuminate\Support\Str::startsWith((string) $current, $prefixes);
                @endphp
                <a href="{{ route($route) }}" class="ea-tab {{ $active ? 'ea-tab-active' : '' }}" @if($active) aria-current="page" @endif>
                    <i class="{{ $tab['icon'] }}" aria-hidden="true"></i> {{ $tab['label'] }}
                    @if(($tab['count'] ?? 0) > 0)
                        <span class="ea-tab-count">{{ $tab['count'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    @endif
</div>
