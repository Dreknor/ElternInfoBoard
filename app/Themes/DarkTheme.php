<?php

namespace App\Themes;

class DarkTheme extends AbstractTheme
{
    public function id(): string
    {
        return 'dark';
    }

    public function name(): string
    {
        return 'Dark Mode';
    }

    public function description(): string
    {
        return 'Dunkles Design für ein augenschonendes Erlebnis bei Nacht.';
    }

    public function previewImage(): ?string
    {
        return '/img/themes/preview-dark.svg';
    }

    public function bodyClasses(): string
    {
        return 'dark-mode';
    }

    public function variables(): array
    {
        return [
            // === Brand Colors ===
            '--color-primary'           => '#3b82f6', // Strahlendes Blau für Buttons/Akzente
            '--color-primary-dark'      => '#2563eb',
            '--color-primary-light'     => '#60a5fa', // Korrigiert: War vorher dunkler als primary
            '--color-secondary'         => '#818cf8',

            // === Sidebar ===
            '--color-sidebar-bg'        => '#0f172a', // Tiefes Slate-Blau
            '--color-sidebar-bg-mid'    => '#1e293b',
            '--color-sidebar-border'    => '#1e293b', // Nahtloser Übergang
            '--color-sidebar-text'      => '#cbd5e1',
            '--color-sidebar-text-muted'=> '#64748b',
            '--color-sidebar-footer-bg' => '#0b1220', // Etwas dunkler zur Abgrenzung
            '--color-sidebar-footer-border' => '#0b1220',
            '--color-sidebar-logo-bg'   => '#0b1220',
            '--color-sidebar-logo-border' => '#0b1220',
            '--color-sidebar-active-bg' => 'rgba(59, 130, 246, 0.15)', // Subtile Transparenz statt Vollfarbe
            '--color-sidebar-hover-bg'  => 'rgba(59, 130, 246, 0.08)',
            '--color-sidebar-hover-text'=> '#f8fafc',
            '--color-sidebar-admin-border' => 'rgba(148, 163, 184, 0.1)',
            '--color-sidebar-admin-label'  => '#94a3b8',
            '--color-sidebar-admin-icon'   => '#94a3b8',

            // === Navbar ===
            '--color-navbar-bg'         => '#1e293b',
            '--color-navbar-text'       => '#f8fafc',
            '--color-navbar-border'     => '#334155',
            '--color-navbar-user-btn-bg'=> '#334155',
            '--color-navbar-user-btn-hover' => '#475569',

            // === Body & Cards ===
            '--color-body-bg'           => '#0f172a', // Dunkelster Hintergrund für maximale räumliche Tiefe der Cards
            '--color-surface-subtle'    => '#0b1220', // Eingedrückte Fläche (Tabellen-Header, alternierend) – etwas dunkler
            '--color-card-bg'           => '#1e293b', // Hebt sich leicht vom Body ab
            '--color-card-border'       => '#334155',
            '--color-text-primary'      => '#f8fafc',
            '--color-text-secondary'    => '#94a3b8',

            // === UI Elements ===
            '--color-mobile-nav-bg'     => '#1e293b',
            '--color-mobile-nav-text'   => '#cbd5e1',
            '--color-input-bg'          => '#0f172a', // Inputs nach "innen" gedrückt
            '--color-input-border'      => '#334155',
            '--color-input-placeholder' => '#64748b',
            '--color-avatar-bg'         => '#3b82f6',
            '--color-badge-bg'          => '#ef4444',

            // === Globals ===
            '--border-radius-base'      => '0.75rem', // 12px wirkt oft moderner als 8px
            '--font-family-base'        => "'Inter', ui-sans-serif, system-ui, sans-serif",
            '--app-bg'                  => '#0f172a',
            '--app-text'                => '#f8fafc',

            // === Layout & Global Headers ===
            '--color-main-header-bg'    => '#0b1220',

            // === LISTE TYP A: "Termine" ===
            '--color-card-a-header-bg'  => '#1e3a8a', // Edleres Dunkelblau statt schrillem Blau
            '--color-card-a-header-text'=> '#eff6ff',
            '--color-card-a-bg'         => '#1e293b',
            '--color-card-a-btn-bg'     => '#2563eb',
            '--color-card-a-btn-text'   => '#ffffff',
            '--color-badge-termin-bg'   => 'rgba(59, 130, 246, 0.2)', // Harmonischere Badges
            '--color-badge-termin-text' => '#93c5fd',

            // === LISTE TYP B: "Eintragungen" ===
            '--color-card-b-header-bg'  => '#334155',
            '--color-card-b-header-text'=> '#f8fafc',
            '--color-card-b-bg'         => '#1e293b',
            '--color-card-b-btn-bg'     => 'transparent',
            '--color-card-b-btn-border' => '#475569', // Dezentere Borders
            '--color-card-b-btn-text'   => '#cbd5e1',
            '--color-card-b-btn-hover'  => '#334155',
            '--color-badge-eintrag-bg'  => '#334155',
            '--color-badge-eintrag-text'=> '#cbd5e1',

            // === Sonder-Status ===
            '--color-badge-inactive-bg' => 'rgba(245, 158, 11, 0.15)',
            '--color-badge-inactive-text'=> '#fcd34d', // Pastelliges Gelb für besseren Kontrast
            '--color-text-success'      => '#34d399', // Emerald (etwas edler als reines Grün)
            '--color-text-main'         => '#f8fafc',
            '--color-text-muted'        => '#94a3b8',

            // === Widget / Karten-Kopfzeilen (Gradients angepasst für Dark Mode) ===
            '--color-widget-primary-from'    => '#2563eb',
            '--color-widget-primary-to'      => '#1d4ed8',
            '--color-widget-primary-border'  => '#1e40af',

            '--color-widget-success-from'    => '#059669',
            '--color-widget-success-to'      => '#047857',
            '--color-widget-success-border'  => '#065f46',
            '--color-widget-success-accent'  => '#34d399',

            '--color-widget-accent-from'     => '#7c3aed', // Violett (kräftig aber elegant)
            '--color-widget-accent-to'       => '#6d28d9',
            '--color-widget-accent-border'   => '#5b21b6',

            '--color-widget-warning-from'    => '#d97706',
            '--color-widget-warning-to'      => '#b45309',
            '--color-widget-warning-border'  => '#92400e',

            // Tönungen für Widget-Hintergründe im Dark Mode (statt unpassender Vollfarben)
            '--color-widget-primary-bg'      => 'rgba(37, 99, 235, 0.1)',
            '--color-widget-success-bg'      => 'rgba(5, 150, 105, 0.1)',
            '--color-widget-accent-bg'       => 'rgba(124, 58, 237, 0.1)',
            '--color-widget-warning-bg'      => 'rgba(217, 119, 6, 0.1)',

            // Gemeinsam
            '--color-widget-header-text'     => '#ffffff',
            '--color-widget-body-bg'         => '#1e293b', // Einheitlich mit `--color-card-bg`

            // === Losung ===
            '--color-losung-header-from'     => '#4f46e5', // Indigo zu Blau Übergang
            '--color-losung-header-to'       => '#3b82f6',
            '--color-losung-icon-bg'         => 'rgba(255, 255, 255, 0.15)', // Dynamischere Icons durch Alpha-Kanäle
            '--color-losung-icon-color'      => '#ffffff',
            '--color-losung-icon2-bg'        => 'rgba(0, 0, 0, 0.25)',
            '--color-losung-icon2-color'     => '#bfdbfe',
            '--color-losung-outer-bg'        => 'linear-gradient(to bottom right, #1e293b, #0f172a)', // Tieferer Verlauf
        ];
    }
}
