<?php

namespace App\Themes;

class NatureTheme extends AbstractTheme
{
    public function id(): string
    {
        return 'nature';
    }

    public function name(): string
    {
        return 'Natur (Grün/Orange)';
    }

    public function description(): string
    {
        return 'Ein naturverbundenes, freundliches Design passend zur Freien Schule Zollernalb.';
    }

    public function previewImage(): ?string
    {
        return '/img/themes/preview-default.svg';
    }

    public function variables(): array
    {
        return [
            '--color-primary'           => '#85603e', // Erdiges Braun (für Buttons & UI-Elemente)
            '--color-primary-dark'      => '#63462d', // Dunkleres Braun
            '--color-primary-light'     => '#f6f9e8', // Sehr zartes Limettengrün
            '--color-secondary'         => '#d5e561', // Frisches Limettengrün (aus den Bannern)
            '--color-sidebar-bg'        => '#ffffff',
            '--color-sidebar-bg-mid'    => '#fcfdf7', // Hauch von Limette im Hintergrund
            '--color-sidebar-border'    => '#e3e4e6',
            '--color-sidebar-text'      => '#696a6b',
            '--color-sidebar-text-muted'=> '#9ca3af',
            '--color-sidebar-footer-bg' => '#fcfdf7',
            '--color-sidebar-footer-border' => '#1f2937',
            '--color-sidebar-logo-bg'   => '#ffffff', // Weißer Hintergrund für das Logo (wie im Bild)
            '--color-sidebar-logo-border' => '#e3e4e6',
            '--color-sidebar-active-bg' => '#85603e', // Braun für aktiven Menüpunkt
            '--color-sidebar-hover-bg'  => '#d5e561', // Limettengrün bei Hover
            '--color-sidebar-hover-text'=> '#382818', // Dunkelbrauner Text bei Hover
            '--color-sidebar-admin-border' => '#ffffff',
            '--color-sidebar-admin-label'  => '#85603e',
            '--color-sidebar-admin-icon'   => '#85603e',
            '--color-navbar-bg'         => '#ffffff',
            '--color-navbar-text'       => '#1f2937',
            '--color-navbar-border'     => '#e5e7eb',
            '--color-navbar-user-btn-bg'=> '#f3f4f6',
            '--color-navbar-user-btn-hover' => '#e5e7eb',
            '--color-body-bg'           => '#fcfdf7',
            '--color-surface-subtle'    => '#f6f9e8', // Zartes Limettengrün
            '--color-card-bg'           => '#ffffff',
            '--color-card-border'       => '#e5e7eb',
            '--color-text-primary'      => '#382818', // Dunkles Schwarzbraun für weicheren Kontrast
            '--color-text-secondary'    => '#6b7280',
            '--color-mobile-nav-bg'     => '#ffffff',
            '--color-mobile-nav-text'   => '#6b7280',
            '--color-input-bg'          => '#ffffff',
            '--color-input-border'      => '#d1d5db',
            '--color-input-placeholder' => '#9ca3af',
            '--color-avatar-bg'         => '#85603e', // Erdbraun
            '--color-badge-bg'          => '#d5e561', // Limettengrün
            '--border-radius-base'      => '0.5rem',
            '--font-family-base'        => "'Inter', ui-sans-serif, system-ui, sans-serif",
            '--app-bg'                  => '#fcfdf7',
            '--app-text'                => '#382818',

            // === Layout & Global Headers ===
            '--color-main-header-bg'    => '#63462d', // Dunkles Braun für die Kopfzeile

            // === LISTE TYP A: "Termine" (Fokus-Typ mit braunem Header) ===
            '--color-card-a-header-bg'  => '#85603e', // Braun
            '--color-card-a-header-text'=> '#ffffff',
            '--color-card-a-bg'         => '#ffffff',
            '--color-card-a-btn-bg'     => '#85603e', // Braun
            '--color-card-a-btn-text'   => '#ffffff',
            '--color-badge-termin-bg'   => '#f6f9e8', // Zartes Limettengrün
            '--color-badge-termin-text' => '#63462d', // Dunkelbraun

            // === LISTE TYP B: "Eintragungen" (Aufgelockerter Typ mit hellem Header) ===
            '--color-card-b-header-bg'  => '#d5e561', // Limettengrün
            '--color-card-b-header-text'=> '#382818', // Dunkelbrauner Text
            '--color-card-b-bg'         => '#ffffff',
            '--color-card-b-btn-bg'     => 'transparent',
            '--color-card-b-btn-border' => '#85603e', // Braun
            '--color-card-b-btn-text'   => '#85603e', // Braun
            '--color-card-b-btn-hover'  => '#f6f9e8', // Zartes Limettengrün
            '--color-badge-eintrag-bg'  => '#e2e8f0',
            '--color-badge-eintrag-text'=> '#475569',

            // === Sonder-Status ===
            '--color-badge-inactive-bg' => '#ffedd5',
            '--color-badge-inactive-text'=> '#c2410c',
            '--color-text-success'      => '#15803d',
            '--color-text-main'         => '#382818',
            '--color-text-muted'        => '#6b7280',

            // === Widget / Karten-Kopfzeilen ===
            // Primär (Braun) – Nachrichten, aktuelle Listen
            '--color-widget-primary-from'    => '#85603e', // Braun
            '--color-widget-primary-to'      => '#63462d', // Dunkelbraun
            '--color-widget-primary-border'  => '#4a3320',
            // Erfolg (Teal) – Beibehalten für CheckIn und Semantik
            '--color-widget-success-from'    => '#0d9488',
            '--color-widget-success-to'      => '#0f766e',
            '--color-widget-success-border'  => '#115e59',
            '--color-widget-success-accent'  => '#14b8a6',
            // Akzent (Olivgrün passend zur Limette, da weißer Text darauf muss)
            '--color-widget-accent-from'     => '#8d9c3e',
            '--color-widget-accent-to'       => '#707c30',
            '--color-widget-accent-border'   => '#576124',
            // Warnung (Amber/Orange) – abgelaufene Listen, nicht eingecheckt
            '--color-widget-warning-from'    => '#d97706',
            '--color-widget-warning-to'      => '#ea580c',
            '--color-widget-warning-border'  => '#9a3412',
            // Helle Widget-Hintergründe (für Karten/Kacheln)
            '--color-widget-primary-bg'      => '#fdfbf9',
            '--color-widget-success-bg'      => '#f0fdf4',
            '--color-widget-accent-bg'       => '#f6f9e8', // Zartes Limettengrün
            '--color-widget-warning-bg'      => '#fffbeb',
            // Gemeinsam
            '--color-widget-header-text'     => '#ffffff',
            '--color-widget-body-bg'         => '#f9fafb',
            // Losung spezifisch (Gradient aus Braun und Oliv)
            '--color-losung-header-from'     => '#85603e',
            '--color-losung-header-to'       => '#8d9c3e',
            '--color-losung-icon-bg'         => '#f6f9e8',
            '--color-losung-icon-color'      => '#8d9c3e',
            '--color-losung-icon2-bg'        => '#fdfbf9',
            '--color-losung-icon2-color'     => '#85603e',
            '--color-losung-outer-bg'        => 'linear-gradient(to bottom right, #fdfbf9, #f6f9e8)',
        ];
    }
}
