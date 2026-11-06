{{-- Dashboard-Widget: auf der Startseite angeheftete Links (Modul „Links“) --}}
<div class="col-12 mb-4">
    <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
        <div class="px-4 py-3 border-b d-flex justify-content-between align-items-center"
             style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border);">
            <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
                <i class="fas fa-link" aria-hidden="true"></i>
                Wichtige Links
            </h5>
            <a href="{{ route('links.index') }}" class="text-sm font-semibold text-decoration-none" style="color: var(--color-widget-header-text);">
                Alle Links <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
        <ul class="p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 list-none m-0">
            @foreach($dashboardLinks as $link)
                <li>@include('links._link', ['link' => $link])</li>
            @endforeach
        </ul>
    </div>
</div>
