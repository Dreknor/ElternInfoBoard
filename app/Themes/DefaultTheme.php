<?php

namespace App\Themes;

class DefaultTheme extends AbstractTheme
{
    public function id(): string
    {
        return 'default';
    }

    public function name(): string
    {
        return 'Standard (Blau)';
    }

    public function description(): string
    {
        return 'Das klassische Design mit dunkler Sidebar und blauen Akzenten.';
    }

    public function previewImage(): ?string
    {
        return '/img/themes/preview-default.svg';
    }

    public function variables(): array
    {
        return [
            '--color-primary'           => '#2563eb',
            '--color-primary-dark'      => '#1d4ed8',
            '--color-primary-light'     => '#eff6ff',
            '--color-secondary'         => '#6366f1',
            '--color-sidebar-bg'        => '#ffffff',
            '--color-sidebar-bg-mid'    => '#f8fafc',
            '--color-sidebar-border'    => '#e3e4e6',
            '--color-sidebar-text'      => '#696a6b',
            '--color-sidebar-text-muted'=> '#9ca3af',
            '--color-sidebar-footer-bg' => '#f8fafc',
            '--color-sidebar-footer-border' => '#1f2937',
            '--color-sidebar-logo-bg'   => '#0b1220',
            '--color-sidebar-logo-border' => '#1f2937',
            '--color-sidebar-active-bg' => '#2563eb',
            '--color-sidebar-hover-bg'  => 'rgba(37,99,235,0.15)',
            '--color-sidebar-hover-text'=> '#93c5fd',
            '--color-sidebar-admin-border' => '#fffff',
            '--color-sidebar-admin-label'  => '#696a6b',
            '--color-sidebar-admin-icon'   => '#4a4e52',
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
            '--color-avatar-bg'         => '#2563eb',
            '--color-badge-bg'          => '#ef4444',
            '--border-radius-base'      => '0.5rem',
            '--font-family-base'        => "'Inter', ui-sans-serif, system-ui, sans-serif",
            '--app-bg'                  => '#f3f4f6',
            '--app-text'                => '#111827',

            // === Layout & Global Headers ===
            '--color-main-header-bg'    => '#1e3a5f', // Dunkles Navy für die Kopfzeile "Aktuelle Listen"

            // === LISTE TYP A: "Termine" (Fokus-Typ mit blauem Header) ===
            '--color-card-a-header-bg'  => '#2563eb',
            '--color-card-a-header-text'=> '#ffffff',
            '--color-card-a-bg'         => '#ffffff',
            '--color-card-a-btn-bg'     => '#2563eb',
            '--color-card-a-btn-text'   => '#ffffff',
            '--color-badge-termin-bg'   => '#dbeafe',
            '--color-badge-termin-text' => '#1e40af',

            // === LISTE TYP B: "Eintragungen" (Aufgelockerter Typ mit hellem Header) ===
            '--color-card-b-header-bg'  => '#f1f5f9',
            '--color-card-b-header-text'=> '#1e293b',
            '--color-card-b-bg'         => '#ffffff',
            '--color-card-b-btn-bg'     => 'transparent',
            '--color-card-b-btn-border' => '#2563eb',
            '--color-card-b-btn-text'   => '#2563eb',
            '--color-card-b-btn-hover'  => '#eff6ff',
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
            '--color-widget-primary-from'    => '#2563eb',
            '--color-widget-primary-to'      => '#1d4ed8',
            '--color-widget-primary-border'  => '#1e40af',
            // Erfolg (Teal) – Termine, CheckIn, Terminlisten
            '--color-widget-success-from'    => '#0d9488',
            '--color-widget-success-to'      => '#0f766e',
            '--color-widget-success-border'  => '#115e59',
            '--color-widget-success-accent'  => '#14b8a6',
            // Akzent (Lila) – Statistiken / Rückmeldungen
            '--color-widget-accent-from'     => '#9333ea',
            '--color-widget-accent-to'       => '#7e22ce',
            '--color-widget-accent-border'   => '#6b21a8',
            // Warnung (Amber/Orange) – abgelaufene Listen, nicht eingecheckt
            '--color-widget-warning-from'    => '#d97706',
            '--color-widget-warning-to'      => '#ea580c',
            '--color-widget-warning-border'  => '#9a3412',
            // Helle Widget-Hintergründe (für Karten/Kacheln)
            '--color-widget-primary-bg'      => '#eff6ff',
            '--color-widget-success-bg'      => '#f0fdf4',
            '--color-widget-accent-bg'       => '#faf5ff',
            '--color-widget-warning-bg'      => '#fffbeb',
            // Gemeinsam
            '--color-widget-header-text'     => '#ffffff',
            '--color-widget-body-bg'         => '#f9fafb',
            // Losung spezifisch
            '--color-losung-header-from'     => '#2563eb',
            '--color-losung-header-to'       => '#4f46e5',
            '--color-losung-icon-bg'         => '#dbeafe',
            '--color-losung-icon-color'      => '#2563eb',
            '--color-losung-icon2-bg'        => '#e0e7ff',
            '--color-losung-icon2-color'     => '#4f46e5',
            '--color-losung-outer-bg'        => 'linear-gradient(to bottom right, #eff6ff, #eef2ff)',
        ];
    }
}


