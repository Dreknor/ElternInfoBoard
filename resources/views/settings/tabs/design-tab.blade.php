<div class="p-2">

    {{-- ===== 1. BESTEHENDE THEME-AUSWAHL ===== --}}
    <form action="{{ url('settings/design') }}" method="post">
        @csrf
        @method('PUT')

        <h6 class="text-base font-bold mb-4 pb-2 border-b" style="color: var(--color-text-primary); border-color: var(--color-card-border);">
            <i class="fas fa-palette text-blue-600 mr-2"></i>
            Design / Theme
        </h6>

        {{-- Standard-Theme --}}
        <div class="form-row mt-1 p-2 border rounded" style="border-color: var(--color-card-border);">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100" style="color: var(--color-text-primary);">
                    Standard-Theme (System)
                    <select name="default_theme" class="form-control"
                            style="background-color: var(--color-input-bg); border-color: var(--color-input-border); color: var(--color-text-primary);">
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
                <div class="small" style="color: var(--color-text-secondary);">
                    Dieses Theme wird für alle Nutzer standardmäßig verwendet, sofern sie kein eigenes wählen.
                </div>
            </div>
        </div>

        {{-- Nutzer dürfen wählen? --}}
        <div class="form-row mt-2 p-2 border rounded" style="border-color: var(--color-card-border);">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 d-flex align-items-center gap-2" style="color: var(--color-text-primary);">
                    <input type="checkbox" name="allow_user_theme" value="1"
                           @if($settings->allow_user_theme ?? true) checked @endif>
                    Nutzer dürfen ihren eigenen Theme wählen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small" style="color: var(--color-text-secondary);">
                    Wenn aktiv, können Nutzer in ihren persönlichen Einstellungen ein abweichendes Design auswählen.
                </div>
            </div>
        </div>

        {{-- Theme-Vorschauen --}}
        <div class="form-row mt-3 p-2">
            <div class="col-12">
                <h6 class="font-semibold mb-2" style="color: var(--color-text-primary);">Verfügbare Themes</h6>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($themes as $theme)
                        <div class="card theme-preview-card"
                             style="width: 220px; cursor: pointer; background-color: var(--color-card-bg); border-color: var(--color-card-border);"
                             onclick="(function(){ var s=document.querySelector('[name=default_theme]'); if(s){ s.value='{{ $theme->id() }}'; } })();">
                            @if($theme->previewImage())
                                <img src="{{ $theme->previewImage() }}"
                                     class="card-img-top"
                                     onerror="this.style.display='none'"
                                     alt="{{ $theme->name() }}">
                            @else
                                <div class="card-img-top d-flex align-items-center justify-content-center"
                                     style="height: 120px; background-color: var(--color-surface-subtle);">
                                    <i class="fas fa-palette fa-3x" style="color: var(--color-text-secondary);"></i>
                                </div>
                            @endif
                            <div class="card-body p-2">
                                @php $vars = $theme->variables(); @endphp
                                <div class="d-flex gap-1 mb-2">
                                    <span title="Primary"   style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{{ $vars['--color-primary']      ?? '#000' }};border:1px solid #ddd;"></span>
                                    <span title="Sidebar"   style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{{ $vars['--color-sidebar-bg']   ?? '#000' }};border:1px solid #ddd;"></span>
                                    <span title="Body BG"   style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{{ $vars['--color-body-bg']      ?? '#000' }};border:1px solid #ddd;"></span>
                                    <span title="Card BG"   style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{{ $vars['--color-card-bg']      ?? '#000' }};border:1px solid #ddd;"></span>
                                    <span title="Text"      style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{{ $vars['--color-text-primary']  ?? '#000' }};border:1px solid #ddd;"></span>
                                </div>
                                <h6 class="card-title mb-1" style="color: var(--color-text-primary);">{{ $theme->name() }}</h6>
                                <p class="card-text small mb-0" style="color: var(--color-text-secondary);">{{ $theme->description() }}</p>
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

    <hr style="border-color: var(--color-card-border); margin: 2rem 0;">

    {{-- ===== 2. EIGENES DESIGN BEARBEITEN ===== --}}
    @php
        use App\Themes\DefaultTheme;
        $defaultVars    = (new DefaultTheme())->variables();
        $customVars     = $customThemeSettings->variables ?? [];

        // Hilfsfunktion: aktuellen Wert ermitteln (Custom > Default)
        $val = fn(string $k) => $customVars[$k] ?? $defaultVars[$k] ?? '';

        // Erkennt einfache HEX-Farben (#rgb oder #rrggbb)
        $isHex = fn(string $v) => (bool) preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', trim($v));

        // Variablen-Gruppen Definition
        $groups = [
            [
                'id'    => 'grp-basis',
                'title' => 'Grundfarben & Layout',
                'icon'  => 'fas fa-fill-drip',
                'vars'  => [
                    '--color-primary'        => 'Primärfarbe',
                    '--color-primary-dark'   => 'Primärfarbe (dunkel)',
                    '--color-primary-light'  => 'Primärfarbe (hell)',
                    '--color-secondary'      => 'Sekundärfarbe',
                    '--color-body-bg'        => 'Seiten-Hintergrund',
                    '--color-surface-subtle' => 'Fläche (subtil)',
                    '--color-card-bg'        => 'Karten-Hintergrund',
                    '--color-card-border'    => 'Karten-Rahmen',
                    '--app-bg'               => 'App-Hintergrund',
                    '--app-text'             => 'App-Text',
                    '--border-radius-base'   => 'Basis-Rundung',
                    '--font-family-base'     => 'Schriftfamilie',
                ],
            ],
            [
                'id'    => 'grp-sidebar',
                'title' => 'Sidebar',
                'icon'  => 'fas fa-columns',
                'vars'  => [
                    '--color-sidebar-bg'           => 'Hintergrund',
                    '--color-sidebar-bg-mid'        => 'Hintergrund (Mitte)',
                    '--color-sidebar-border'        => 'Rahmen',
                    '--color-sidebar-text'          => 'Text',
                    '--color-sidebar-text-muted'    => 'Text (gedimmt)',
                    '--color-sidebar-footer-bg'     => 'Footer-Hintergrund',
                    '--color-sidebar-footer-border' => 'Footer-Rahmen',
                    '--color-sidebar-logo-bg'       => 'Logo-Hintergrund',
                    '--color-sidebar-logo-border'   => 'Logo-Rahmen',
                    '--color-sidebar-active-bg'     => 'Aktiv-Hintergrund',
                    '--color-sidebar-hover-bg'      => 'Hover-Hintergrund',
                    '--color-sidebar-hover-text'    => 'Hover-Text',
                    '--color-sidebar-admin-border'  => 'Admin-Bereich Rahmen',
                    '--color-sidebar-admin-label'   => 'Admin-Label',
                    '--color-sidebar-admin-icon'    => 'Admin-Icon',
                ],
            ],
            [
                'id'    => 'grp-navbar',
                'title' => 'Navbar & Navigation',
                'icon'  => 'fas fa-bars',
                'vars'  => [
                    '--color-navbar-bg'              => 'Navbar-Hintergrund',
                    '--color-navbar-text'            => 'Navbar-Text',
                    '--color-navbar-border'          => 'Navbar-Rahmen',
                    '--color-navbar-user-btn-bg'     => 'User-Button Hintergrund',
                    '--color-navbar-user-btn-hover'  => 'User-Button Hover',
                    '--color-mobile-nav-bg'          => 'Mobile Nav Hintergrund',
                    '--color-mobile-nav-text'        => 'Mobile Nav Text',
                ],
            ],
            [
                'id'    => 'grp-text',
                'title' => 'Text & Eingabefelder',
                'icon'  => 'fas fa-font',
                'vars'  => [
                    '--color-text-primary'      => 'Text (primär)',
                    '--color-text-secondary'    => 'Text (sekundär)',
                    '--color-text-muted'        => 'Text (gedimmt)',
                    '--color-text-success'      => 'Text (Erfolg)',
                    '--color-text-main'         => 'Text (Haupt)',
                    '--color-input-bg'          => 'Eingabefeld Hintergrund',
                    '--color-input-border'      => 'Eingabefeld Rahmen',
                    '--color-input-placeholder' => 'Platzhalter-Text',
                    '--color-avatar-bg'         => 'Avatar Hintergrund',
                    '--color-badge-bg'          => 'Badge Hintergrund',
                ],
            ],
            [
                'id'    => 'grp-cards',
                'title' => 'Listen-Karten',
                'icon'  => 'fas fa-th-large',
                'vars'  => [
                    '--color-main-header-bg'      => 'Hauptlisten-Header',
                    '--color-card-a-header-bg'    => 'Typ A: Header Hintergrund',
                    '--color-card-a-header-text'  => 'Typ A: Header Text',
                    '--color-card-a-bg'           => 'Typ A: Karte Hintergrund',
                    '--color-card-a-btn-bg'       => 'Typ A: Button Hintergrund',
                    '--color-card-a-btn-text'     => 'Typ A: Button Text',
                    '--color-badge-termin-bg'     => 'Termin-Badge Hintergrund',
                    '--color-badge-termin-text'   => 'Termin-Badge Text',
                    '--color-card-b-header-bg'    => 'Typ B: Header Hintergrund',
                    '--color-card-b-header-text'  => 'Typ B: Header Text',
                    '--color-card-b-bg'           => 'Typ B: Karte Hintergrund',
                    '--color-card-b-btn-bg'       => 'Typ B: Button Hintergrund',
                    '--color-card-b-btn-border'   => 'Typ B: Button Rahmen',
                    '--color-card-b-btn-text'     => 'Typ B: Button Text',
                    '--color-card-b-btn-hover'    => 'Typ B: Button Hover',
                    '--color-badge-eintrag-bg'    => 'Eintrag-Badge Hintergrund',
                    '--color-badge-eintrag-text'  => 'Eintrag-Badge Text',
                    '--color-badge-inactive-bg'   => 'Inaktiv-Badge Hintergrund',
                    '--color-badge-inactive-text' => 'Inaktiv-Badge Text',
                ],
            ],
            [
                'id'    => 'grp-widgets',
                'title' => 'Widgets & Kacheln',
                'icon'  => 'fas fa-layer-group',
                'vars'  => [
                    '--color-widget-primary-from'   => 'Primär: Gradient Start',
                    '--color-widget-primary-to'     => 'Primär: Gradient Ende',
                    '--color-widget-primary-border' => 'Primär: Rahmen',
                    '--color-widget-primary-bg'     => 'Primär: Hintergrund (hell)',
                    '--color-widget-success-from'   => 'Erfolg: Gradient Start',
                    '--color-widget-success-to'     => 'Erfolg: Gradient Ende',
                    '--color-widget-success-border' => 'Erfolg: Rahmen',
                    '--color-widget-success-accent' => 'Erfolg: Akzent',
                    '--color-widget-success-bg'     => 'Erfolg: Hintergrund (hell)',
                    '--color-widget-accent-from'    => 'Akzent: Gradient Start',
                    '--color-widget-accent-to'      => 'Akzent: Gradient Ende',
                    '--color-widget-accent-border'  => 'Akzent: Rahmen',
                    '--color-widget-accent-bg'      => 'Akzent: Hintergrund (hell)',
                    '--color-widget-warning-from'   => 'Warnung: Gradient Start',
                    '--color-widget-warning-to'     => 'Warnung: Gradient Ende',
                    '--color-widget-warning-border' => 'Warnung: Rahmen',
                    '--color-widget-warning-bg'     => 'Warnung: Hintergrund (hell)',
                    '--color-widget-header-text'    => 'Widget-Header Text',
                    '--color-widget-body-bg'        => 'Widget-Body Hintergrund',
                ],
            ],
            [
                'id'    => 'grp-warning-banner',
                'title' => 'Warnung Alert-Banner',
                'icon'  => 'fas fa-flag',
                'vars'  => [
                    '--color-warning-bg'             => 'Hintergrund',
                    '--color-warning-border'         => 'Rahmen',
                    '--color-warning-icon-bg'        => 'Icon-Hintergrund',
                    '--color-warning-text'           => 'Text (Überschrift)',
                    '--color-warning-text-secondary' => 'Text (Beschreibung)',
                    '--color-warning-btn-bg'         => 'Button Hintergrund',
                    '--color-warning-btn-hover'      => 'Button Hover',
                ],
            ],
            [
                'id'    => 'grp-losung',
                'title' => 'Losung-Widget',
                'icon'  => 'fas fa-book-open',
                'vars'  => [
                    '--color-losung-header-from'  => 'Header Gradient Start',
                    '--color-losung-header-to'    => 'Header Gradient Ende',
                    '--color-losung-icon-bg'      => 'Icon 1 Hintergrund',
                    '--color-losung-icon-color'   => 'Icon 1 Farbe',
                    '--color-losung-icon2-bg'     => 'Icon 2 Hintergrund',
                    '--color-losung-icon2-color'  => 'Icon 2 Farbe',
                    '--color-losung-outer-bg'     => 'Äußerer Hintergrund',
                ],
            ],
        ];
    @endphp

    <h6 class="text-base font-bold mb-1" style="color: var(--color-text-primary);">
        <i class="fas fa-paint-brush text-purple-600 mr-2"></i>
        Eigenes Design bearbeiten
    </h6>
    <p class="small mb-3" style="color: var(--color-text-secondary);">
        Erstelle ein individuelles Design, indem du einzelne Farbwerte und Eigenschaften anpasst.
        Wähle anschließend oben <strong>„Eigenes Design"</strong> als Standard-Theme aus.
    </p>

    {{-- ===== GRUNDLAGE KOPIEREN ===== --}}
    @php
        $allThemeVars = [];
        foreach($themes as $t) {
            $allThemeVars[$t->id()] = [
                'name'      => $t->name(),
                'variables' => $t->variables(),
            ];
        }
    @endphp

    <div class="p-3 rounded border mb-4"
         style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border-color: #c4b5fd;">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <i class="fas fa-copy" style="color: #7c3aed;"></i>
            <strong class="small" style="color: #5b21b6;">Vorhandenes Design als Grundlage übernehmen</strong>
        </div>
        <p class="small mt-1 mb-2" style="color: #6d28d9;">
            Wähle ein bestehendes Theme aus und übernimm dessen Werte als Startpunkt für dein eigenes Design.
            Deine bisherigen Eingaben werden dadurch ersetzt.
        </p>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <select id="copy-theme-source" class="form-control form-control-sm"
                    style="max-width: 260px; background-color: var(--color-input-bg); border-color: #c4b5fd; color: var(--color-text-primary);">
                <option value="">– Theme auswählen –</option>
                @foreach($themes as $t)
                    @if($t->id() !== 'custom')
                        <option value="{{ $t->id() }}">{{ $t->name() }}</option>
                    @endif
                @endforeach
            </select>
            <button type="button" id="btn-copy-theme"
                    class="btn btn-sm"
                    style="background-color: #7c3aed; color: #fff; border: none;"
                    onclick="copyThemeAsBase()">
                <i class="fas fa-copy mr-1"></i>
                Als Grundlage übernehmen
            </button>
        </div>
    </div>

    <script id="all-theme-vars-data" type="application/json">
        {!! json_encode($allThemeVars) !!}
    </script>

    <form action="{{ route('settings.custom-theme.update') }}" method="post" id="custom-theme-form">
        @csrf
        @method('PUT')

        {{-- Name & Beschreibung --}}
        <div class="p-3 rounded border mb-3" style="background-color: var(--color-surface-subtle); border-color: var(--color-card-border);">
            <div class="row">
                <div class="col-md-5">
                    <label class="small font-weight-bold mb-1" style="color: var(--color-text-primary);">
                        Name des eigenen Designs
                    </label>
                    <input type="text" name="custom_theme_name"
                           value="{{ $customThemeSettings->name ?? 'Eigenes Design' }}"
                           class="form-control form-control-sm"
                           style="background-color: var(--color-input-bg); border-color: var(--color-input-border); color: var(--color-text-primary);"
                           placeholder="z.B. Schule Musterstadt">
                </div>
                <div class="col-md-7">
                    <label class="small font-weight-bold mb-1" style="color: var(--color-text-primary);">
                        Beschreibung
                    </label>
                    <input type="text" name="custom_theme_description"
                           value="{{ $customThemeSettings->description ?? '' }}"
                           class="form-control form-control-sm"
                           style="background-color: var(--color-input-bg); border-color: var(--color-input-border); color: var(--color-text-primary);"
                           placeholder="Kurze Beschreibung des Designs">
                </div>
            </div>

            {{-- Vorschau-Leiste: zeigt Primary, Sidebar, Body, Card, Text --}}
            <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                <span class="small" style="color: var(--color-text-secondary);">Vorschau:</span>
                @foreach(['--color-primary','--color-sidebar-bg','--color-body-bg','--color-card-bg','--color-text-primary'] as $pv)
                    <span id="preview-{{ Str::slug($pv) }}"
                          title="{{ $pv }}"
                          style="display:inline-block;width:28px;height:28px;border-radius:6px;background:{{ $val($pv) }};border:2px solid rgba(0,0,0,0.15);"></span>
                @endforeach
                <span class="small ml-2" style="color: var(--color-text-secondary);">
                    Wird live aktualisiert
                </span>
            </div>
        </div>

        {{-- Variablen-Gruppen --}}
        <div class="accordion" id="theme-var-accordion">
            @foreach($groups as $group)
                <div class="card mb-2" style="background-color: var(--color-card-bg); border-color: var(--color-card-border);"
                     x-data="{ open: false }">
                    <div class="card-header p-0"
                         style="background-color: var(--color-surface-subtle); border-color: var(--color-card-border);">
                        <button class="btn btn-link btn-block text-left d-flex align-items-center justify-content-between px-3 py-2"
                                type="button"
                                @click="open = !open"
                                :aria-expanded="open.toString()"
                                style="color: var(--color-text-primary); text-decoration: none;">
                            <span>
                                <i class="{{ $group['icon'] }} mr-2" style="color: var(--color-primary); width:16px;"></i>
                                <strong>{{ $group['title'] }}</strong>
                                <span class="badge badge-secondary ml-2" style="font-size:0.65rem;">{{ count($group['vars']) }}</span>
                            </span>
                            <i class="fas fa-chevron-down"
                               style="font-size:0.75rem; transition:transform .2s;"
                               :style="open ? 'transform:rotate(180deg)' : 'transform:rotate(0deg)'"></i>
                        </button>
                    </div>
                    <div x-show="open" x-cloak style="display:none;">
                        <div class="card-body p-3">
                            <div class="row">
                                @foreach($group['vars'] as $varKey => $varLabel)
                                    @php
                                        $currentVal = $val($varKey);
                                        $hexColor   = $isHex($currentVal);
                                        $inputId    = 'var-' . md5($varKey);
                                        $isCustom   = isset($customVars[$varKey]);
                                    @endphp
                                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                                        <label class="d-flex align-items-center gap-1 mb-1"
                                               style="font-size:0.72rem; color: var(--color-text-secondary);"
                                               title="{{ $varKey }}">
                                            <span class="text-truncate" style="max-width:160px;">{{ $varLabel }}</span>
                                            @if($isCustom)
                                                <span class="badge badge-primary ml-1" style="font-size:0.6rem; padding:2px 5px;">angepasst</span>
                                            @endif
                                        </label>
                                        <div class="d-flex align-items-center gap-1">
                                            @if($hexColor)
                                                <input type="color"
                                                       value="{{ $currentVal }}"
                                                       class="theme-color-picker"
                                                       data-target="{{ $inputId }}"
                                                       style="width:34px;height:34px;padding:2px;border-radius:4px;cursor:pointer;border:1px solid var(--color-input-border);background:none;"
                                                       title="Farbauswahl für {{ $varLabel }}">
                                            @endif
                                            <input type="text"
                                                   id="{{ $inputId }}"
                                                   name="vars[{{ $varKey }}]"
                                                   value="{{ $currentVal }}"
                                                   class="form-control form-control-sm font-mono theme-var-input {{ $isCustom ? 'border-primary' : '' }}"
                                                   style="font-size:0.75rem; background-color: var(--color-input-bg); border-color: {{ $isCustom ? 'var(--color-primary)' : 'var(--color-input-border)' }}; color: var(--color-text-primary);"
                                                   data-var-key="{{ $varKey }}"
                                                   data-default="{{ $defaultVars[$varKey] ?? '' }}"
                                                   placeholder="{{ $defaultVars[$varKey] ?? '' }}">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Aktions-Buttons --}}
        <div class="d-flex gap-2 mt-3 flex-wrap">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i>
                Eigenes Design speichern
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btn-reset-to-default">
                <i class="fas fa-undo mr-1"></i>
                Formular auf Standardwerte zurücksetzen
            </button>
        </div>
    </form>

    {{-- Reset auf DB-Defaults --}}
    <form action="{{ route('settings.custom-theme.reset') }}" method="post" class="mt-2"
          onsubmit="return confirm('Alle eigenen Anpassungen löschen und auf Standard-Werte zurücksetzen?');">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-trash-alt mr-1"></i>
            Alle Anpassungen löschen
        </button>
    </form>

</div>

@push('js')
<script>
(function () {
    // Color-Picker ↔ Text-Input synchronisieren
    document.querySelectorAll('.theme-color-picker').forEach(function (picker) {
        var targetId = picker.getAttribute('data-target');
        var textInput = document.getElementById(targetId);
        if (!textInput) return;

        picker.addEventListener('input', function () {
            textInput.value = picker.value;
            textInput.dispatchEvent(new Event('input'));
        });

        textInput.addEventListener('input', function () {
            var v = textInput.value.trim();
            if (/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v)) {
                picker.value = v;
            }
            updateCustomBadge(textInput);
            updatePreview(textInput.getAttribute('data-var-key'), v);
        });

        textInput.addEventListener('change', function () {
            updateCustomBadge(textInput);
        });
    });

    // Nur Text-Inputs (ohne Farbpicker) auch auf Änderungen prüfen
    document.querySelectorAll('.theme-var-input').forEach(function (input) {
        input.addEventListener('input', function () {
            updateCustomBadge(input);
            updatePreview(input.getAttribute('data-var-key'), input.value.trim());
        });
    });

    function updateCustomBadge(input) {
        var defaultVal = input.getAttribute('data-default') || '';
        var current    = input.value.trim();
        if (current !== defaultVal && current !== '') {
            input.style.borderColor = 'var(--color-primary)';
        } else {
            input.style.borderColor = 'var(--color-input-border)';
        }
    }

    function updatePreview(varKey, value) {
        var previewKeys = ['--color-primary','--color-sidebar-bg','--color-body-bg','--color-card-bg','--color-text-primary'];
        if (previewKeys.indexOf(varKey) === -1) return;
        var slug = varKey.replace(/[^a-zA-Z0-9]/g, '-').replace(/^-+/, '');
        var el = document.getElementById('preview-' + slug);
        if (el && value) el.style.background = value;
    }


    // Theme als Grundlage kopieren
    window.copyThemeAsBase = function () {
        var select = document.getElementById('copy-theme-source');
        var themeId = select ? select.value : '';
        if (!themeId) {
            alert('Bitte zuerst ein Theme auswählen.');
            return;
        }
        var dataEl = document.getElementById('all-theme-vars-data');
        if (!dataEl) return;
        var allVars = JSON.parse(dataEl.textContent || '{}');
        var themeData = allVars[themeId];
        if (!themeData) {
            alert('Theme-Daten nicht gefunden.');
            return;
        }
        if (!confirm('Die Werte von „' + themeData.name + '" als Grundlage übernehmen? Deine bisherigen Eingaben im Formular werden ersetzt.')) return;

        var variables = themeData.variables;
        document.querySelectorAll('.theme-var-input').forEach(function (input) {
            var key = input.getAttribute('data-var-key');
            if (key && variables.hasOwnProperty(key)) {
                input.value = variables[key];
                // Farbpicker synchronisieren
                var colorPicker = document.querySelector('[data-target="' + input.id + '"]');
                if (colorPicker && /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(variables[key])) {
                    colorPicker.value = variables[key];
                }
                // Badge & Vorschau aktualisieren
                updateCustomBadge(input);
                updatePreview(key, input.value.trim());
            }
        });
    };

    // Formular auf Default-Werte zurücksetzen (nur Formular, nicht DB)
    document.getElementById('btn-reset-to-default').addEventListener('click', function () {
        if (!confirm('Alle Felder auf Standard-Werte zurücksetzen? (Noch nicht gespeicherte Änderungen gehen verloren.)')) return;
        document.querySelectorAll('.theme-var-input').forEach(function (input) {
            var def = input.getAttribute('data-default') || '';
            input.value = def;
            input.style.borderColor = 'var(--color-input-border)';
            // Farbpicker mitzogen
            var colorPicker = document.querySelector('[data-target="' + input.id + '"]');
            if (colorPicker && /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(def)) {
                colorPicker.value = def;
            }
        });
    });
})();
</script>
@endpush
