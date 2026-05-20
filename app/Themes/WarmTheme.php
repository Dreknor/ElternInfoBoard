<?php

namespace App\Themes;

class WarmTheme extends AbstractTheme
{
    public function id(): string
    {
        return 'warm';
    }

    public function name(): string
    {
        return 'Warm (Orange)';
    }

    public function description(): string
    {
        return 'Ein warmes, freundliches Design in Erdtönen.';
    }

    public function previewImage(): ?string
    {
        return '/img/themes/preview-warm.png';
    }

    public function variables(): array
    {
        return [
            '--color-primary'           => '#ea580c',
            '--color-primary-dark'      => '#c2410c',
            '--color-primary-light'     => '#fff7ed',
            '--color-secondary'         => '#d97706',
            '--color-sidebar-bg'        => '#431407',
            '--color-sidebar-bg-mid'    => '#7c2d12',
            '--color-sidebar-border'    => '#7c2d12',
            '--color-sidebar-text'      => '#fed7aa',
            '--color-sidebar-text-muted'=> '#fdba74',
            '--color-sidebar-footer-bg' => '#2a0c04',
            '--color-sidebar-footer-border' => '#7c2d12',
            '--color-sidebar-logo-bg'   => '#2a0c04',
            '--color-sidebar-logo-border' => '#7c2d12',
            '--color-sidebar-active-bg' => '#ea580c',
            '--color-sidebar-hover-bg'  => 'rgba(234,88,12,0.2)',
            '--color-sidebar-hover-text'=> '#fdba74',
            '--color-navbar-bg'         => '#fffbeb',
            '--color-navbar-text'       => '#431407',
            '--color-navbar-border'     => '#fed7aa',
            '--color-navbar-user-btn-bg'=> '#fff7ed',
            '--color-navbar-user-btn-hover' => '#ffedd5',
            '--color-body-bg'           => '#fff7ed',
            '--color-card-bg'           => '#fffbeb',
            '--color-card-border'       => '#fed7aa',
            '--color-text-primary'      => '#431407',
            '--color-text-secondary'    => '#9a3412',
            '--color-mobile-nav-bg'     => '#fffbeb',
            '--color-mobile-nav-text'   => '#9a3412',
            '--color-input-bg'          => '#fffbeb',
            '--color-input-border'      => '#fed7aa',
            '--color-input-placeholder' => '#fdba74',
            '--color-avatar-bg'         => '#ea580c',
            '--color-badge-bg'          => '#dc2626',
            '--border-radius-base'      => '0.75rem',
            '--font-family-base'        => "'Inter', ui-sans-serif, system-ui, sans-serif",
            '--app-bg'                  => '#fff7ed',
            '--app-text'                => '#431407',

            // === Layout & Global Headers ===
            '--color-main-header-bg'    => '#431407', // Tiefes Dunkelbraun für die Kopfzeile

            // === LISTE TYP A: "Termine" (Oranger Header) ===
            '--color-card-a-header-bg'  => '#ea580c',
            '--color-card-a-header-text'=> '#ffffff',
            '--color-card-a-bg'         => '#fffbeb',
            '--color-card-a-btn-bg'     => '#ea580c',
            '--color-card-a-btn-text'   => '#ffffff',
            '--color-badge-termin-bg'   => '#ffedd5',
            '--color-badge-termin-text' => '#9a3412',

            // === LISTE TYP B: "Eintragungen" (Heller Warmton als Header) ===
            '--color-card-b-header-bg'  => '#fef3c7',
            '--color-card-b-header-text'=> '#431407',
            '--color-card-b-bg'         => '#fffbeb',
            '--color-card-b-btn-bg'     => 'transparent',
            '--color-card-b-btn-border' => '#ea580c',
            '--color-card-b-btn-text'   => '#c2410c',
            '--color-card-b-btn-hover'  => '#fff7ed',
            '--color-badge-eintrag-bg'  => '#fde68a',
            '--color-badge-eintrag-text'=> '#78350f',

            // === Sonder-Status ===
            '--color-badge-inactive-bg' => '#fef9c3',
            '--color-badge-inactive-text'=> '#854d0e',
            '--color-text-success'      => '#15803d',
            '--color-text-main'         => '#431407',
            '--color-text-muted'        => '#9a3412',

            // === Widget / Karten-Kopfzeilen ===
            '--color-widget-primary-from'    => '#ea580c',
            '--color-widget-primary-to'      => '#c2410c',
            '--color-widget-primary-border'  => '#9a3412',
            '--color-widget-success-from'    => '#d97706',
            '--color-widget-success-to'      => '#b45309',
            '--color-widget-success-border'  => '#92400e',
            '--color-widget-success-accent'  => '#f59e0b',
            '--color-widget-accent-from'     => '#dc2626',
            '--color-widget-accent-to'       => '#b91c1c',
            '--color-widget-accent-border'   => '#991b1b',
            '--color-widget-warning-from'    => '#ca8a04',
            '--color-widget-warning-to'      => '#a16207',
            '--color-widget-warning-border'  => '#854d0e',
            // Helle Widget-Hintergründe (für Karten/Kacheln)
            '--color-widget-primary-bg'      => '#fff7ed',
            '--color-widget-success-bg'      => '#fffbeb',
            '--color-widget-accent-bg'       => '#fff1f2',
            '--color-widget-warning-bg'      => '#fefce8',
            '--color-widget-header-text'     => '#ffffff',
            '--color-widget-body-bg'         => '#fff7ed',
            '--color-losung-header-from'     => '#ea580c',
            '--color-losung-header-to'       => '#d97706',
            '--color-losung-icon-bg'         => '#ffedd5',
            '--color-losung-icon-color'      => '#ea580c',
            '--color-losung-icon2-bg'        => '#fef9c3',
            '--color-losung-icon2-color'     => '#ca8a04',
            '--color-losung-outer-bg'        => 'linear-gradient(to bottom right, #fff7ed, #fffbeb)',
        ];
    }
}


