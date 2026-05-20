<div class="p-2">
    <form action="{{ url('settings/design') }}" method="post">
        @csrf
        @method('PUT')

        <h6 class="text-base font-bold text-gray-800 dark:text-gray-100 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
            <i class="fas fa-palette text-blue-600 mr-2"></i>
            Design / Theme
        </h6>

        {{-- Standard-Theme --}}
        <div class="form-row mt-1 p-2 border border-gray-200 dark:border-gray-700 rounded">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Standard-Theme (System)
                    <select name="default_theme" class="form-control">
                        @foreach($themes as $theme)
                            <option value="{{ $theme->id() }}"
                                @if(($settings->default_theme ?? 'default') === $theme->id()) selected @endif>
                                {{ $theme->name() }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small text-gray-500 dark:text-gray-400">
                    Dieses Theme wird für alle Nutzer standardmäßig verwendet, sofern sie kein eigenes wählen.
                </div>
            </div>
        </div>

        {{-- Nutzer dürfen wählen? --}}
        <div class="form-row mt-2 p-2 border border-gray-200 dark:border-gray-700 rounded">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 d-flex align-items-center gap-2">
                    <input type="checkbox" name="allow_user_theme" value="1"
                           @if($settings->allow_user_theme ?? true) checked @endif>
                    Nutzer dürfen ihren eigenen Theme wählen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small text-gray-500 dark:text-gray-400">
                    Wenn aktiv, können Nutzer in ihren persönlichen Einstellungen ein abweichendes Design auswählen.
                </div>
            </div>
        </div>

        {{-- Theme-Vorschauen --}}
        <div class="form-row mt-3 p-2">
            <div class="col-12">
                <h6 class="font-semibold mb-2">Verfügbare Themes</h6>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($themes as $theme)
                        <div class="card theme-preview-card"
                             style="width: 220px; cursor: pointer;"
                             onclick="(function(){ var s=document.querySelector('[name=default_theme]'); if(s){ s.value='{{ $theme->id() }}'; } })();">
                            @if($theme->previewImage())
                                <img src="{{ $theme->previewImage() }}"
                                     class="card-img-top"
                                     onerror="this.style.display='none'"
                                     alt="{{ $theme->name() }}">
                            @else
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                     style="height: 120px;">
                                    <i class="fas fa-palette fa-3x text-muted"></i>
                                </div>
                            @endif
                            <div class="card-body p-2">
                                {{-- Mini-Farbpalette aus den wichtigsten Variablen --}}
                                @php $vars = $theme->variables(); @endphp
                                <div class="d-flex gap-1 mb-2">
                                    <span title="Primary" style="display:inline-block;width:18px;height:18px;border-radius:50%;background: {{ $vars['--color-primary'] ?? '#000' }};border:1px solid #ddd;"></span>
                                    <span title="Sidebar" style="display:inline-block;width:18px;height:18px;border-radius:50%;background: {{ $vars['--color-sidebar-bg'] ?? '#000' }};border:1px solid #ddd;"></span>
                                    <span title="Body BG" style="display:inline-block;width:18px;height:18px;border-radius:50%;background: {{ $vars['--color-body-bg'] ?? '#000' }};border:1px solid #ddd;"></span>
                                    <span title="Card BG" style="display:inline-block;width:18px;height:18px;border-radius:50%;background: {{ $vars['--color-card-bg'] ?? '#000' }};border:1px solid #ddd;"></span>
                                    <span title="Text" style="display:inline-block;width:18px;height:18px;border-radius:50%;background: {{ $vars['--color-text-primary'] ?? '#000' }};border:1px solid #ddd;"></span>
                                </div>
                                <h6 class="card-title mb-1">{{ $theme->name() }}</h6>
                                <p class="card-text small text-muted mb-0">{{ $theme->description() }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="form-row mt-3">
            <button type="submit" class="btn btn-success btn-block">
                <i class="fas fa-save mr-1"></i>
                Design-Einstellungen speichern
            </button>
        </div>
    </form>
</div>

