<div class="rounded-lg border-2 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200"
     style="background-color: var(--color-card-bg); border-color: var(--color-card-border);">
    <!-- Header -->
    <div class="px-3 py-2" style="background-color: var(--color-primary);">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <h6 class="text-sm font-semibold text-white mb-0">
                Bilder ({{count($nachricht->getMedia('images'))}})
            </h6>
        </div>
    </div>

    <div class="p-2" style="background-color: var(--color-body-bg);">
        <x-image-gallery :images="$nachricht->getMedia('images')->sortBy('name')"
                         compact
                         :show-names="$nachricht->rueckmeldung?->type == 'bild'"/>
    </div>
</div>

