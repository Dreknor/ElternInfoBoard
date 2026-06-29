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
        return 'Warm (Pastell)';
    }

    public function description(): string
    {
        return 'Ein modernes, frisches Design in weichen, warmen Pastell- und Erdtönen.';
    }

    public function previewImage(): ?string
    {
        return '/img/themes/preview-warm.svg';
    }

    public function variables(): array
    {
        return [
            // === Hauptfarben (Weiches Pfirsich/Orange) ===
            '--color-primary'           => '#ffb088', // Pastelliges Pfirsich-Orange
            '--color-primary-dark'      => '#e88d65',
            '--color-primary-light'     => '#ffefe8',
            '--color-secondary'         => '#ffd180', // Sanftes Pastell-Gold

            // === Sidebar (Helles, warmes Creme-Beige statt tiefem Dunkelbraun) ===
            '--color-sidebar-bg'        => '#f5ece5',
            '--color-sidebar-bg-mid'    => '#e8dbce',
            '--color-sidebar-border'    => '#e8dbce',
            '--color-sidebar-text'      => '#785c4d', // Warmes, abgedunkeltes Taupe
            '--color-sidebar-text-muted'=> '#9e8576',
            '--color-sidebar-footer-bg' => '#eee0d6',
            '--color-sidebar-footer-border' => '#e8dbce',
            '--color-sidebar-logo-bg'   => '#f5ece5',
            '--color-sidebar-logo-border' => '#e8dbce',
            '--color-sidebar-active-bg' => '#ffb088',
            '--color-sidebar-hover-bg'  => 'rgba(255, 176, 136, 0.25)',
            '--color-sidebar-hover-text'=> '#d66e42',
            '--color-sidebar-admin-border' => 'rgba(158, 133, 118, 0.3)',
            '--color-sidebar-admin-label'  => '#9e8576',
            '--color-sidebar-admin-icon'   => '#9e8576',

            // === Navbar & Body (Luftig, hell, sehr subtil warm) ===
            '--color-navbar-bg'         => '#ffffff',
            '--color-navbar-text'       => '#785c4d',
            '--color-navbar-border'     => '#f5ece5',
            '--color-navbar-user-btn-bg'=> '#fdfbf9',
            '--color-navbar-user-btn-hover' => '#f5ece5',
            '--color-body-bg'           => '#fdfbf9', // Gebrochenes Warmweiß
            '--color-surface-subtle'    => '#f5ece5',
            '--color-card-bg'           => '#ffffff',
            '--color-card-border'       => '#f0e5df',
            '--color-text-primary'      => '#5c463c', // Dunkles, warmes Braun (nicht rein schwarz)
            '--color-text-secondary'    => '#8c7368',
            '--color-mobile-nav-bg'     => '#ffffff',
            '--color-mobile-nav-text'   => '#785c4d',
            '--color-input-bg'          => '#ffffff',
            '--color-input-border'      => '#e8dbce',
            '--color-input-placeholder' => '#bda89f',
            '--color-avatar-bg'         => '#ffb088',
            '--color-badge-bg'          => '#fca5a5', // Pastell-Rot
            '--border-radius-base'      => '1rem', // Etwas runder für den modernen, weichen Pastell-Look
            '--font-family-base'        => "'Inter', ui-sans-serif, system-ui, sans-serif",
            '--app-bg'                  => '#fdfbf9',
            '--app-text'                => '#5c463c',

            // === Layout & Global Headers ===
            '--color-main-header-bg'    => '#f4e3d7', // Sanftes, warmes Sand/Pfirsich

            // === LISTE TYP A: "Termine" (Weiches Pfirsich) ===
            '--color-card-a-header-bg'  => '#ffb088',
            '--color-card-a-header-text'=> '#5c463c', // Dunkle Schrift für besseren Kontrast auf Pastell
            '--color-card-a-bg'         => '#ffffff',
            '--color-card-a-btn-bg'     => '#ffb088',
            '--color-card-a-btn-text'   => '#5c463c',
            '--color-badge-termin-bg'   => '#ffe8d6',
            '--color-badge-termin-text' => '#c27a5d',

            // === LISTE TYP B: "Eintragungen" (Weiches Pastellgelb/Creme) ===
            '--color-card-b-header-bg'  => '#fbeec1',
            '--color-card-b-header-text'=> '#5c463c',
            '--color-card-b-bg'         => '#ffffff',
            '--color-card-b-btn-bg'     => 'transparent',
            '--color-card-b-btn-border' => '#f1c27d',
            '--color-card-b-btn-text'   => '#b68d40',
            '--color-card-b-btn-hover'  => '#fdf8ea',
            '--color-badge-eintrag-bg'  => '#fdf3d1',
            '--color-badge-eintrag-text'=> '#9d752c',

            // === Sonder-Status ===
            '--color-badge-inactive-bg' => '#f2ece9',
            '--color-badge-inactive-text'=> '#a39189',
            '--color-text-success'      => '#7fb083', // Sanftes Pastell-Grün
            '--color-text-main'         => '#5c463c',
            '--color-text-muted'        => '#8c7368',

            // === Widget / Karten-Kopfzeilen (Pastell-Verläufe) ===
            '--color-widget-primary-from'    => '#ffb088',
            '--color-widget-primary-to'      => '#ffc8a8',
            '--color-widget-primary-border'  => '#f5d5c6',
            '--color-widget-success-from'    => '#a3c9a6',
            '--color-widget-success-to'      => '#c1e0c3',
            '--color-widget-success-border'  => '#b1d6b4',
            '--color-widget-success-accent'  => '#86b08a',
            '--color-widget-accent-from'     => '#fca5a5',
            '--color-widget-accent-to'       => '#fecaca',
            '--color-widget-accent-border'   => '#f9a8d4',
            '--color-widget-warning-from'    => '#fde047',
            '--color-widget-warning-to'      => '#fef08a',
            '--color-widget-warning-border'  => '#fef08a',

            // Helle Widget-Hintergründe
            '--color-widget-primary-bg'      => '#fff5f0',
            '--color-widget-success-bg'      => '#f4fcf5',
            '--color-widget-accent-bg'       => '#fef2f2',
            '--color-widget-warning-bg'      => '#fffbeb',
            '--color-widget-header-text'     => '#5c463c',
            '--color-widget-body-bg'         => '#ffffff',

            // Warning Alert-Banner (Warm: Texte an Warm-Theme-Palette angepasst)
            '--color-warning-bg'             => '#fffbeb',
            '--color-warning-border'         => '#f59e0b',
            '--color-warning-icon-bg'        => '#f59e0b',
            '--color-warning-text'           => '#5c463c',
            '--color-warning-text-secondary' => '#8c7368',
            '--color-warning-btn-bg'         => '#d97706',
            '--color-warning-btn-hover'      => '#b45309',

            // === Losung Widgets ===
            '--color-losung-header-from'     => '#ffb088',
            '--color-losung-header-to'       => '#ffd180',
            '--color-losung-icon-bg'         => '#ffffff',
            '--color-losung-icon-color'      => '#ffb088',
            '--color-losung-icon2-bg'        => '#ffffff',
            '--color-losung-icon2-color'     => '#ffd180',
            '--color-losung-outer-bg'        => 'linear-gradient(to bottom right, #fff5f0, #fdf8ea)',
        ];
    }
}
