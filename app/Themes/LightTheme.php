<?php

namespace App\Themes;

class LightTheme extends AbstractTheme
{
    public function id(): string
    {
        // Ein eindeutiger Bezeichner für das Theme
        return 'light_theme';
    }

    public function name(): string
    {
        // Der Anzeigename in den Einstellungen
        return 'Light Theme';
    }

    public function description(): string
    {
        // Eine kurze Beschreibung des Farbschemas
        return 'Ein Farbschema, das direkt von den drei Kernfarben  (gebranntes Orange, tiefes Rot, dunkles Anthrazit) abgeleitet ist.';
    }

    public function previewImage(): ?string
    {
        // Beibehaltene Vorschaugrafik
        return '/img/themes/preview-light.svg';
    }

    public function variables(): array
    {
        return [
            // === Globale Farben ===
            // Primär: Das tiefe Rot (#B01D21)
            '--color-primary'           => '#B01D21',
            '--color-primary-dark'      => '#8E1A1D', // Etwas dunkleres Rot
            '--color-primary-light'     => '#FDECEE', // Sehr helles Rot für dezente Hintergründe
            // Sekundär: Das gebrannte Orange (#EB7F29)
            '--color-secondary'         => '#EB7F29',

            // === Sidebar ===
            '--color-sidebar-bg'        => '#ffffff',
            '--color-sidebar-bg-mid'    => '#f8fafc',
            '--color-sidebar-border'    => '#e3e4e6',
            // Text: Das dunkle Anthrazitgrau (#4C4C4C)
            '--color-sidebar-text'      => '#4C4C4C',
            '--color-sidebar-text-muted'=> '#9ca3af',
            '--color-sidebar-footer-bg' => '#f8fafc',
            '--color-sidebar-footer-border' => '#1f2937',
            '--color-sidebar-logo-bg'   => '#ffffff', // Logo-Feld bleibt weiß
            '--color-sidebar-logo-border' => '#e3e4e6',
            '--color-sidebar-active-bg' => '#B01D21', // Aktive Elemente in Primärrot
            '--color-sidebar-active-text'=> '#ffffff',
            '--color-sidebar-hover-bg'  => '#EB7F29', // Hover-Effekt in Sekundärorange
            '--color-sidebar-hover-text'=> '#ffffff',
            '--color-sidebar-admin-border' => '#fffff',
            // Admin-Beschriftungen in Anthrazit
            '--color-sidebar-admin-label'  => '#4C4C4C',
            '--color-sidebar-admin-icon'   => '#4C4C4C',

            // === Navbar ===
            '--color-navbar-bg'         => '#ffffff',
            '--color-navbar-text'       => '#4C4C4C', // Anthrazit
            '--color-navbar-border'     => '#e5e7eb',
            '--color-navbar-user-btn-bg'=> '#f3f4f6',
            '--color-navbar-user-btn-hover' => '#e5e7eb',

            // === Body & Global ===
            '--color-body-bg'           => '#f3f4f6',
            '--color-surface-subtle'    => '#ffffff', // Oberflächen bleiben sauber weiß
            '--color-card-bg'           => '#ffffff',
            '--color-card-border'       => '#e5e7eb',
            // Haupttext in Anthrazit
            '--color-text-primary'      => '#4C4C4C',
            '--color-text-secondary'    => '#6b7280',
            '--color-mobile-nav-bg'     => '#ffffff',
            '--color-mobile-nav-text'   => '#6b7280',
            '--color-input-bg'          => '#ffffff',
            '--color-input-border'      => '#d1d5db',
            '--color-input-placeholder' => '#9ca3af',
            '--color-avatar-bg'         => '#B01D21', // Avatar in Primärrot
            '--color-badge-bg'          => '#B01D21', // Badges in Primärrot
            '--border-radius-base'      => '0.5rem',
            '--font-family-base'        => "'Inter', ui-sans-serif, system-ui, sans-serif",
            '--app-bg'                  => '#f3f4f6',
            '--app-text'                => '#4C4C4C', // Anthrazit

            // === Layout & Global Headers ===
            '--color-main-header-bg'    => '#B01D21', // Primärrot für den Hauptheaderbereich

            // === LISTE TYP A: "Termine" (Fokus-Typ, jetzt in Primärrot statt Blau) ===
            '--color-card-a-header-bg'  => '#B01D21',
            '--color-card-a-header-text'=> '#ffffff',
            '--color-card-a-bg'         => '#ffffff',
            '--color-card-a-btn-bg'     => '#B01D21',
            '--color-card-a-btn-text'   => '#ffffff',
            '--color-badge-termin-bg'   => '#FDECEE', // Sehr helles Rot
            '--color-badge-termin-text' => '#B01D21',

            // === LISTE TYP B: "Eintragungen" (Aufgelockerter Typ, jetzt in Sekundärorange) ===
            '--color-card-b-header-bg'  => '#FFF0E1', // Sehr helles Orange
            '--color-card-b-header-text'=> '#4C4C4C',
            '--color-card-b-bg'         => '#ffffff',
            '--color-card-b-btn-bg'     => 'transparent',
            '--color-card-b-btn-border' => '#EB7F29',
            '--color-card-b-btn-text'   => '#EB7F29',
            '--color-card-b-btn-hover'  => '#FFF0E1',
            '--color-badge-eintrag-bg'  => '#FFF0E1', // Sehr helles Orange
            '--color-badge-eintrag-text'=> '#EB7F29',

            // === Sonder-Status ===
            '--color-badge-inactive-bg' => '#ffedd5', // Bernstein/Orange (beibehalten, passt gut)
            '--color-badge-inactive-text'=> '#c2410c',
            '--color-text-success'      => '#4C4C4C', // Erfolg in Anthrazit (Neutral, da kein Grün im Screenshot)
            '--color-text-main'         => '#4C4C4C', // Anthrazit
            '--color-text-muted'        => '#6b7280',

            // === Widget / Karten-Kopfzeilen ===
            // Nutzt die triadische Palette des Screenshots: Rot, Orange, Anthrazit

            // Primär (Rot) - Nachrichten, aktuelle Listen
            '--color-widget-primary-from'    => '#B01D21',
            '--color-widget-primary-to'      => '#8E1A1D', // Dunkles Rot
            '--color-widget-primary-border'  => '#6E1316', // Noch dunkleres Rot
            '--color-widget-primary-bg'      => '#FDECEE', // Hellroter Hintergrund

            // Erfolg (Sekundärorange, ersetzt Teal) - Termine, CheckIn, Terminlisten
            '--color-widget-success-from'    => '#EB7F29',
            '--color-widget-success-to'      => '#FFF0E1', // Helles Orange
            '--color-widget-success-border'  => '#C86A1D', // Dunkles Orange
            '--color-widget-success-accent'  => '#EB7F29', // Lebendiges Orange
            '--color-widget-success-bg'      => '#FFF0E1', // Hellorangener Hintergrund

            // Akzent (Anthrazit, ersetzt Lila) - Statistiken / Rückmeldungen
            '--color-widget-accent-from'     => '#4C4C4C',
            '--color-widget-accent-to'       => '#6b7280', // Helles Anthrazit
            '--color-widget-accent-border'   => '#363636', // Dunkles Anthrazit
            '--color-widget-accent-bg'       => '#f3f4f6', // Hellgrauer Hintergrund

            // Warnung (Bernstein/Originalorange, beibehalten für klare Warnfunktion)
            '--color-widget-warning-from'    => '#d97706',
            '--color-widget-warning-to'      => '#ea580c',
            '--color-widget-warning-border'  => '#9a3412',
            '--color-widget-warning-bg'      => '#fffbeb',

            // Warning Alert-Banner
            '--color-warning-bg'             => '#fef3c7', // Beibehalten
            '--color-warning-border'         => '#f59e0b',
            '--color-warning-icon-bg'        => '#f59e0b',
            '--color-warning-text'           => '#78350f',
            '--color-warning-text-secondary' => '#92400e',
            '--color-warning-btn-bg'         => '#d97706',
            '--color-warning-btn-hover'      => '#b45309',

            // Losung spezifisch (Kombiniert alle drei Farben)
            '--color-losung-header-from'     => '#B01D21', // Rot
            '--color-losung-header-to'       => '#EB7F29', // Orange
            '--color-losung-icon-bg'         => '#FDECEE', // Hellrot
            '--color-losung-icon-color'      => '#B01D21',
            '--color-losung-icon2-bg'        => '#FFF0E1', // Hellorange
            '--color-losung-icon2-color'     => '#EB7F29',
            '--color-losung-outer-bg'        => 'linear-gradient(to bottom right, #FDECEE, #FFF0E1)', // Verlauf von Hellrot zu Hellorange
        ];
    }
}
