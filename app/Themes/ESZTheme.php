<?php

namespace App\Themes;

class ESZTheme extends AbstractTheme
{
    public function id(): string
    {
        return 'ESZ';
    }

    public function name(): string
    {
        return 'ESZ Radebeul';
    }

    public function description(): string
    {
        return 'Das individuelle Design für das Evangelische Schulzentrum Radebeul, mit den charakteristischen Blau- und Pinktönen des Logos, angepasst an die Bedürfnisse der Schule.';
    }

    public function previewImage(): ?string
    {
        return '/img/themes/preview-esz.svg';
    }

    public function variables(): array
    {
        return [
            '--color-primary'           => '#425a8f', // Logo Blau
            '--color-primary-dark'      => '#31446e', // Dunkleres Logo Blau
            '--color-primary-light'     => '#edf1f8', // Sehr helles Logo Blau
            '--color-secondary'         => '#d92853', // Logo Pink/Rot
            '--color-sidebar-bg'        => '#ffffff',
            '--color-sidebar-bg-mid'    => '#f8fafc',
            '--color-sidebar-border'    => '#e3e4e6',
            '--color-sidebar-text'      => '#696a6b',
            '--color-sidebar-text-muted'=> '#9ca3af',
            '--color-sidebar-footer-bg' => '#f8fafc',
            '--color-sidebar-footer-border' => '#1f2937',
            '--color-sidebar-logo-bg'   => '#1a243b', // Angepasst an das dunklere Blau
            '--color-sidebar-logo-border' => '#1f2937',
            '--color-sidebar-active-bg' => '#425a8f', // Logo Blau
            '--color-sidebar-hover-bg'  => '#a1b4d9', // Aufgehelltes Blau für Hover
            '--color-sidebar-hover-text'=> '#1f2b45',
            '--color-sidebar-admin-border' => '#ffffff',
            '--color-sidebar-admin-label'  => '#31446e',
            '--color-sidebar-admin-icon'   => '#31446e',
            '--color-navbar-bg'         => '#ffffff',
            '--color-navbar-text'       => '#1f2937',
            '--color-navbar-border'     => '#e5e7eb',
            '--color-navbar-user-btn-bg'=> '#f3f4f6',
            '--color-navbar-user-btn-hover' => '#e5e7eb',
            '--color-body-bg'           => '#f3f4f6',
            '--color-surface-subtle'    => '#f9fafb', // bg-gray-50 Äquivalent
            '--color-card-bg'           => '#ffffff',
            '--color-card-border'       => '#e5e7eb',
            '--color-text-primary'      => '#111827',
            '--color-text-secondary'    => '#6b7280',
            '--color-mobile-nav-bg'     => '#ffffff',
            '--color-mobile-nav-text'   => '#6b7280',
            '--color-input-bg'          => '#ffffff',
            '--color-input-border'      => '#d1d5db',
            '--color-input-placeholder' => '#9ca3af',
            '--color-avatar-bg'         => '#425a8f', // Logo Blau
            '--color-badge-bg'          => '#d92853', // Logo Pink/Rot
            '--border-radius-base'      => '0.5rem',
            '--font-family-base'        => "'Inter', ui-sans-serif, system-ui, sans-serif",
            '--app-bg'                  => '#f3f4f6',
            '--app-text'                => '#111827',

            // === Layout & Global Headers ===
            '--color-main-header-bg'    => '#253554', // Dunkles Logo Navy für die Kopfzeile "Aktuelle Listen"

            // === LISTE TYP A: "Termine" (Fokus-Typ mit blauem Header) ===
            '--color-card-a-header-bg'  => '#425a8f', // Logo Blau
            '--color-card-a-header-text'=> '#ffffff',
            '--color-card-a-bg'         => '#ffffff',
            '--color-card-a-btn-bg'     => '#425a8f', // Logo Blau
            '--color-card-a-btn-text'   => '#ffffff',
            '--color-badge-termin-bg'   => '#edf1f8',
            '--color-badge-termin-text' => '#31446e',

            // === LISTE TYP B: "Eintragungen" (Aufgelockerter Typ mit hellem Header) ===
            '--color-card-b-header-bg'  => '#f1f5f9',
            '--color-card-b-header-text'=> '#1e293b',
            '--color-card-b-bg'         => '#ffffff',
            '--color-card-b-btn-bg'     => 'transparent',
            '--color-card-b-btn-border' => '#425a8f', // Logo Blau
            '--color-card-b-btn-text'   => '#425a8f', // Logo Blau
            '--color-card-b-btn-hover'  => '#edf1f8', // Helles Blau
            '--color-badge-eintrag-bg'  => '#e2e8f0',
            '--color-badge-eintrag-text'=> '#475569',

            // === Sonder-Status ===
            '--color-badge-inactive-bg' => '#ffedd5',
            '--color-badge-inactive-text'=> '#c2410c',
            '--color-text-success'      => '#15803d', // Linke Zahl bei Buchungen
            '--color-text-main'         => '#111827',
            '--color-text-muted'        => '#6b7280',

            // === Widget / Karten-Kopfzeilen ===
            // Primär (Blau) – Nachrichten, aktuelle Listen
            '--color-widget-primary-from'    => '#425a8f', // Logo Blau
            '--color-widget-primary-to'      => '#31446e',
            '--color-widget-primary-border'  => '#253554',
            // Erfolg (Teal) – Termine, CheckIn, Terminlisten (Beibehalten für Semantik)
            '--color-widget-success-from'    => '#0d9488',
            '--color-widget-success-to'      => '#0f766e',
            '--color-widget-success-border'  => '#115e59',
            '--color-widget-success-accent'  => '#14b8a6',
            // Akzent (Logo Pink/Rot) – Statistiken / Rückmeldungen
            '--color-widget-accent-from'     => '#d92853', // Logo Pink/Rot
            '--color-widget-accent-to'       => '#b81d43',
            '--color-widget-accent-border'   => '#961534',
            // Warnung (Amber/Orange) – abgelaufene Listen, nicht eingecheckt (Beibehalten für Semantik)
            '--color-widget-warning-from'    => '#d97706',
            '--color-widget-warning-to'      => '#ea580c',
            '--color-widget-warning-border'  => '#9a3412',
            // Helle Widget-Hintergründe (für Karten/Kacheln)
            '--color-widget-primary-bg'      => '#edf1f8', // Passendes helles Blau
            '--color-widget-success-bg'      => '#f0fdf4',
            '--color-widget-accent-bg'       => '#fce8ec', // Passendes helles Pink
            '--color-widget-warning-bg'      => '#fffbeb',
            // Gemeinsam
            '--color-widget-header-text'     => '#ffffff',
            '--color-widget-body-bg'         => '#f9fafb',
            // Losung spezifisch (Gradient aus den beiden Logo-Farben)
            '--color-losung-header-from'     => '#425a8f', // Logo Blau
            '--color-losung-header-to'       => '#d92853', // Logo Pink/Rot
            '--color-losung-icon-bg'         => '#edf1f8',
            '--color-losung-icon-color'      => '#425a8f',
            '--color-losung-icon2-bg'        => '#fce8ec',
            '--color-losung-icon2-color'     => '#d92853',
            '--color-losung-outer-bg'        => 'linear-gradient(to bottom right, #edf1f8, #fce8ec)',
        ];
    }
}
