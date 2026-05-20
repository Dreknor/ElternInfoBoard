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
        return '/img/themes/preview-dark.png';
    }

    public function bodyClasses(): string
    {
        return 'dark-mode';
    }

    public function variables(): array
    {
        return [
            '--color-primary'           => '#3b82f6',
            '--color-primary-dark'      => '#2563eb',
            '--color-primary-light'     => '#1e3a5f',
            '--color-secondary'         => '#818cf8',
            '--color-sidebar-bg'        => '#0f172a',
            '--color-sidebar-bg-mid'    => '#1e293b',
            '--color-sidebar-border'    => '#1e293b',
            '--color-sidebar-text'      => '#cbd5e1',
            '--color-sidebar-text-muted'=> '#94a3b8',
            '--color-sidebar-footer-bg' => '#0b1220',
            '--color-sidebar-footer-border' => '#1e293b',
            '--color-sidebar-logo-bg'   => '#0b1220',
            '--color-sidebar-logo-border' => '#1e293b',
            '--color-sidebar-active-bg' => '#3b82f6',
            '--color-sidebar-hover-bg'  => 'rgba(59,130,246,0.2)',
            '--color-sidebar-hover-text'=> '#93c5fd',
            '--color-navbar-bg'         => '#1e293b',
            '--color-navbar-text'       => '#f1f5f9',
            '--color-navbar-border'     => '#334155',
            '--color-navbar-user-btn-bg'=> '#334155',
            '--color-navbar-user-btn-hover' => '#475569',
            '--color-body-bg'           => '#0f172a',
            '--color-card-bg'           => '#1e293b',
            '--color-card-border'       => '#334155',
            '--color-text-primary'      => '#f1f5f9',
            '--color-text-secondary'    => '#94a3b8',
            '--color-mobile-nav-bg'     => '#1e293b',
            '--color-mobile-nav-text'   => '#94a3b8',
            '--color-input-bg'          => '#334155',
            '--color-input-border'      => '#475569',
            '--color-input-placeholder' => '#64748b',
            '--color-avatar-bg'         => '#3b82f6',
            '--color-badge-bg'          => '#ef4444',
            '--border-radius-base'      => '0.5rem',
            '--font-family-base'        => "'Inter', ui-sans-serif, system-ui, sans-serif",
            '--app-bg'                  => '#0f172a',
            '--app-text'                => '#f1f5f9',

            // === Layout & Global Headers ===
            '--color-main-header-bg'    => '#0b1220', // Tiefstes Dunkel für die Kopfzeile

            // === LISTE TYP A: "Termine" (Blauer Header im Dark Mode) ===
            '--color-card-a-header-bg'  => '#1d4ed8',
            '--color-card-a-header-text'=> '#ffffff',
            '--color-card-a-bg'         => '#1e293b',
            '--color-card-a-btn-bg'     => '#3b82f6',
            '--color-card-a-btn-text'   => '#ffffff',
            '--color-badge-termin-bg'   => '#1e3a5f',
            '--color-badge-termin-text' => '#93c5fd',

            // === LISTE TYP B: "Eintragungen" (Etwas helleres Dunkel als Header) ===
            '--color-card-b-header-bg'  => '#334155',
            '--color-card-b-header-text'=> '#f1f5f9',
            '--color-card-b-bg'         => '#1e293b',
            '--color-card-b-btn-bg'     => 'transparent',
            '--color-card-b-btn-border' => '#3b82f6',
            '--color-card-b-btn-text'   => '#60a5fa',
            '--color-card-b-btn-hover'  => '#1e3a5f',
            '--color-badge-eintrag-bg'  => '#334155',
            '--color-badge-eintrag-text'=> '#94a3b8',

            // === Sonder-Status ===
            '--color-badge-inactive-bg' => '#422006',
            '--color-badge-inactive-text'=> '#fb923c',
            '--color-text-success'      => '#4ade80', // Helles Grün für Dark Mode
            '--color-text-main'         => '#f1f5f9',
            '--color-text-muted'        => '#94a3b8',

            // === Widget / Karten-Kopfzeilen ===
            '--color-widget-primary-from'    => '#3b82f6',
            '--color-widget-primary-to'      => '#2563eb',
            '--color-widget-primary-border'  => '#1d4ed8',
            '--color-widget-success-from'    => '#0f9488',
            '--color-widget-success-to'      => '#0d9488',
            '--color-widget-success-border'  => '#0f766e',
            '--color-widget-success-accent'  => '#2dd4bf',
            '--color-widget-accent-from'     => '#a855f7',
            '--color-widget-accent-to'       => '#9333ea',
            '--color-widget-accent-border'   => '#7e22ce',
            '--color-widget-warning-from'    => '#f59e0b',
            '--color-widget-warning-to'      => '#d97706',
            '--color-widget-warning-border'  => '#b45309',
            // Helle Widget-Hintergründe (für Karten/Kacheln)
            '--color-widget-primary-bg'      => '#1e3a5f',
            '--color-widget-success-bg'      => '#134e4a',
            '--color-widget-accent-bg'       => '#3b0764',
            '--color-widget-warning-bg'      => '#451a03',
            // Gemeinsam
            '--color-widget-header-text'     => '#ffffff',
            '--color-widget-body-bg'         => '#253347',
            '--color-losung-header-from'     => '#3b82f6',
            '--color-losung-header-to'       => '#6366f1',
            '--color-losung-icon-bg'         => '#1e3a5f',
            '--color-losung-icon-color'      => '#60a5fa',
            '--color-losung-icon2-bg'        => '#312e81',
            '--color-losung-icon2-color'     => '#a5b4fc',
            '--color-losung-outer-bg'        => 'linear-gradient(to bottom right, #1e293b, #1e1b4b)',
        ];
    }
}


