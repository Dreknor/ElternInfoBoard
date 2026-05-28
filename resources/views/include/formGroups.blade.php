<div class="form-group mb-0">
    <p class="label-control mb-2">Für welche Gruppen?</p>

    {{-- "Alle Gruppen" Checkbox --}}
    <label class="flex items-center gap-3 cursor-pointer mb-3 p-2 rounded-lg border"
           style="border-color: var(--color-card-border); background: var(--color-body-bg);">
        <input type="checkbox" name="gruppen[]" value="all" id="checkboxAll"
               class="w-5 h-5 rounded cursor-pointer flex-shrink-0"
               style="accent-color: var(--color-primary);" />
        <span class="text-sm font-bold select-none" style="color: var(--color-text-primary);">
            Alle Gruppen <span class="font-normal text-xs" style="color: var(--color-text-muted);">(außer geschützte)</span>
        </span>
    </label>

    {{-- Bereichs-Checkboxen --}}
    @foreach($gruppen->unique('bereich')->pluck('bereich') as $bereich)
        @if($bereich != "")
            <label class="flex items-center gap-3 cursor-pointer mb-2">
                <input type="checkbox" name="gruppen[]" value="{{ $bereich }}"
                       id="checkbox{{ $bereich }}"
                       class="w-5 h-5 rounded cursor-pointer flex-shrink-0"
                       style="accent-color: var(--color-primary);" />
                <span class="text-sm font-semibold select-none" style="color: var(--color-text-primary);">
                    {{ $bereich }}
                </span>
            </label>
        @endif
    @endforeach

    {{-- Einzelne Gruppen --}}
    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
        @foreach($gruppen as $gruppe)
            <label class="flex items-center gap-2 cursor-pointer py-1" for="{{ $gruppe->name }}">
                <input type="checkbox"
                       id="{{ $gruppe->name }}"
                       name="gruppen[]"
                       value="{{ $gruppe->id }}"
                       class="w-4 h-4 rounded cursor-pointer flex-shrink-0"
                       style="accent-color: var(--color-primary);"
                       @if(isset($post)   && $post->groups->contains($gruppe->id)  ||
                           isset($user)   && $user->groups->contains($gruppe)       ||
                           isset($liste)  && $liste->groups->contains($gruppe)      ||
                           isset($groups) && $selectedGroups->contains($gruppe)) checked @endif>
                <span class="text-sm select-none" style="color: var(--color-text-primary);">
                    {{ $gruppe->name }}
                    @if($gruppe->protected)
                        <i class="fas fa-lock text-xs" style="color: var(--color-text-muted);" title="Geschützte Gruppe"></i>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
</div>
