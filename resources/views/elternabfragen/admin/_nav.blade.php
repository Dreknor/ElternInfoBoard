{{-- Kopf und Reiter der Verwaltung „Elternabfragen“ --}}
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
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-clipboard-check text-blue-600" aria-hidden="true"></i>
                {{ $title ?? 'Elternabfragen' }}
            </h1>
            @isset($subtitle)
                <p class="text-sm text-gray-600 mt-1">{{ $subtitle }}</p>
            @endisset
        </div>
        {{ $actions ?? '' }}
    </div>
    @if(count($tabs) > 1)
        <nav class="flex flex-wrap gap-1 border-b border-gray-200" aria-label="Bereiche">
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
                <a href="{{ route($route) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 -mb-px border-b-2 text-sm font-medium
                          {{ $active ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="{{ $tab['icon'] }}" aria-hidden="true"></i> {{ $tab['label'] }}
                    @if(($tab['count'] ?? 0) > 0)
                        <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-amber-500 rounded-full">{{ $tab['count'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    @endif
</div>
