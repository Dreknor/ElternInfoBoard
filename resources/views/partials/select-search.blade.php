{{--
    Durchsuchbare Auswahllisten (Select2) für <select class="js-select-search">.
    Platzhalter = Text der leeren ersten Option; mehrfaches @include lädt Select2 nur einmal.
--}}
@once
    @push('css')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
        <style>
            .select2-container--default .select2-selection--single {
                height: calc(1.5em + .75rem + 2px);
                border: 1px solid var(--color-input-border, #d1d5db);
                border-radius: var(--border-radius-base, .25rem);
                background-color: var(--color-input-bg, #fff);
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: calc(1.5em + .75rem);
                color: var(--color-text-primary, #111827);
            }
            .select2-container--default .select2-selection--single .select2-selection__placeholder {
                color: var(--color-input-placeholder, #9ca3af);
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: calc(1.5em + .75rem);
            }
            .select2-dropdown {
                border-color: var(--color-input-border, #d1d5db);
                background-color: var(--color-card-bg, #fff);
                color: var(--color-text-primary, #111827);
            }
            .select2-container--default .select2-search--dropdown .select2-search__field {
                border-color: var(--color-input-border, #d1d5db);
                background-color: var(--color-input-bg, #fff);
                color: var(--color-text-primary, #111827);
            }
            .select2-container--default .select2-results__option--highlighted[aria-selected] {
                background-color: var(--color-primary, #2563eb);
            }
            /* In form-inline sonst nur so breit wie der Platzhalter */
            .form-inline .select2-container {
                min-width: 18rem;
                max-width: 100%;
            }
        </style>
    @endpush

    @push('js')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(function () {
                $('select.js-select-search').each(function () {
                    const $select = $(this);
                    const placeholder = $select.find('option[value=""]').first().text();

                    $select.select2({
                        placeholder: placeholder || 'Suchen …',
                        allowClear: !$select.prop('required'),
                        width: $select.closest('.form-inline').length ? 'resolve' : '100%',
                        language: {
                            noResults: () => 'Keine Treffer',
                            searching: () => 'Suche …',
                        },
                    });

                    // Abstandsklassen (mr-2, mb-2 …) auf das sichtbare Feld übertragen
                    const spacing = (this.className.match(/\bm[trblxy]?-\d\b/g) || []).join(' ');
                    $select.next('.select2-container').addClass(spacing);
                });

                // Suchfeld beim Öffnen direkt fokussieren
                $(document).on('select2:open', () => {
                    document.querySelector('.select2-container--open .select2-search__field')?.focus();
                });
            });
        </script>
    @endpush
@endonce
