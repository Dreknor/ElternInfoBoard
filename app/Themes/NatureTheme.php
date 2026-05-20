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
        return 'Natur (Grün)';
    }

    public function description(): string
    {
        return 'Ein frisches, grünes Design.';
    }

    public function previewImage(): ?string
    {
        return '/img/themes/preview-nature.png';
    }

    public function variables(): array
    {
        return [
            // === Brand Colors (Ruhiges Salbei & dezenter Akzent) ===
            '--color-primary'           => '#4d7c5d', // Gedämpftes Salbeigrün (Ruhe & Fokus)
            '--color-primary-dark'      => '#3b5f47', // Hover-Zustand für Primary-Elemente
            '--color-primary-light'     => '#f2f7f4', // Sehr helles Salbei-Weiß für Card-Hintergründe Typ A

            '--color-secondary'         => '#64748b', // Neutraler Schieferton für sekundäre Icons/Texte

            // === Sidebar ===
            '--color-sidebar-bg'        => '#1a3325', // Dunkles Waldgrün
            '--color-sidebar-bg-mid'    => '#2d4a36',
            '--color-sidebar-border'    => '#2d4a36',
            '--color-sidebar-text'      => '#d1e8d8',
            '--color-sidebar-text-muted'=> '#a3c4a8',
            '--color-sidebar-footer-bg' => '#111f18',
            '--color-sidebar-footer-border' => '#2d4a36',
            '--color-sidebar-logo-bg'   => '#111f18',
            '--color-sidebar-logo-border' => '#2d4a36',
            '--color-sidebar-active-bg' => '#4d7c5d',
            '--color-sidebar-hover-bg'  => 'rgba(77,124,93,0.2)',
            '--color-sidebar-hover-text'=> '#a3c4a8',
            '--color-sidebar-admin-border' => 'rgba(163,196,168,0.35)', // gedämpftes Salbei-Grün
            '--color-sidebar-admin-label'  => '#a3c4a8',                // Muted-Text
            '--color-sidebar-admin-icon'   => '#a3c4a8',

            // === Navbar ===
            '--color-navbar-bg'         => '#ffffff',
            '--color-navbar-text'       => '#1e293b',
            '--color-navbar-border'     => '#e2e8e0',
            '--color-navbar-user-btn-bg'=> '#f2f7f4',
            '--color-navbar-user-btn-hover' => '#e6f0ea',

            // === Cards & Text ===
            '--color-card-bg'           => '#ffffff',
            '--color-text-primary'      => '#1e293b',
            '--color-text-secondary'    => '#64748b',

            // === Mobile Nav ===
            '--color-mobile-nav-bg'     => '#ffffff',
            '--color-mobile-nav-text'   => '#64748b',

            // === Inputs ===
            '--color-input-bg'          => '#ffffff',
            '--color-input-border'      => '#c8d8c8',
            '--color-input-placeholder' => '#a3c4a8',

            // === Avatar & Badge ===
            '--color-avatar-bg'         => '#4d7c5d',
            '--color-badge-bg'          => '#ef4444',

            // === App ===
            '--app-bg'                  => '#f8fafc',
            '--app-text'                => '#1e293b',

            // === Layout & Global Headers ===
            '--color-main-header-bg'    => '#2d4a36', // Dunkles, edles Tannengrün für die Hauptleiste oben
            '--color-body-bg'           => '#f8fafc', // Absolut ruhiges, hellgraues Off-White für den Hintergrund
            '--color-surface-subtle'    => '#f2f7f4', // Dezentes Salbeigrün-Weiß für Tabellen-Header, Footers etc.
            '--border-radius-base'      => '0.5rem',  // Etwas reduzierter für einen moderneren, cleanen Look
            '--font-family-base'        => "'Inter', ui-sans-serif, system-ui, sans-serif", // Weg von der Serifenschrift

            // === LISTE TYP A: "Termine" (Fokus-Typ mit grünem Header) ===
            '--color-card-a-header-bg'  => '#4d7c5d', // Salbeigrün für Header
            '--color-card-a-header-text'=> '#ffffff',
            '--color-card-a-bg'         => '#ffffff',
            '--color-card-a-btn-bg'     => '#4d7c5d', // Voller Button (Primary)
            '--color-card-a-btn-text'   => '#ffffff',
            '--color-badge-termin-bg'   => '#e6f0ea', // Sehr dezentes, helles Salbeigrün
            '--color-badge-termin-text' => '#2d4a36',

            // === LISTE TYP B: "Eintragungen" (Aufgelockerter Typ mit hellem Header) ===
            '--color-card-b-header-bg'  => '#f0e9cc', // Neutraler, heller Header (bricht die grüne Wand)
            '--color-card-b-header-text'=> '#1e293b', // Dunkler Text für Lesbarkeit
            '--color-card-b-bg'         => '#ffffff',
            '--color-card-b-btn-bg'     => 'transparent', // Outline-Button statt Vollfläche
            '--color-card-b-btn-border' => '#4d7c5d', // Grüner Rahmen
            '--color-card-b-btn-text'   => '#4d7c5d', // Grüner Text
            '--color-card-b-btn-hover'  => '#f2f7f4',
            '--color-badge-eintrag-bg'  => '#e2e8f0', // Neutrales Hellgrau
            '--color-badge-eintrag-text'=> '#475569',

            // === Allgemeine Card-Elemente ===
            '--color-card-border'       => '#e2e8f0', // Dünne, saubere Trennlinien
            '--color-text-main'         => '#1e293b', // Fast Schwarz für Fließtext
            '--color-text-muted'        => '#64748b', // Grau für Datumsanzeigen unten

            // === Sonder-Status ===
            '--color-badge-inactive-bg' => '#ffedd5', // Soft-Orange für "Inaktiv" (nicht so stechend)
            '--color-badge-inactive-text'=> '#c2410c',
            '--color-text-success'      => '#15803d', // Für die linke Zahl bei "Bisherige Buchungen"

            // === Widget / Karten-Kopfzeilen ===
            // Primär (Salbeigrün) – Nachrichten, aktuelle Listen
            '--color-widget-primary-from'    => '#4d7c5d',
            '--color-widget-primary-to'      => '#3b5f47',
            '--color-widget-primary-border'  => '#2d4a36',
            // Erfolg (Teal) – Termine, CheckIn, Terminlisten
            '--color-widget-success-from'    => '#0d9488',
            '--color-widget-success-to'      => '#0f766e',
            '--color-widget-success-border'  => '#115e59',
            '--color-widget-success-accent'  => '#14b8a6',
            // Akzent (Amber/Gold) – AGs, Statistiken, Rückmeldungen
            '--color-widget-accent-from'     => '#b45309', // Amber-700 – warmes Erdgold
            '--color-widget-accent-to'       => '#92400e', // Amber-800
            '--color-widget-accent-border'   => '#78350f', // Amber-900
            // Warnung (Amber/Orange) – abgelaufene Listen, nicht eingecheckt
            '--color-widget-warning-from'    => '#d97706',
            '--color-widget-warning-to'      => '#ea580c',
            '--color-widget-warning-border'  => '#9a3412',
            // Helle Widget-Hintergründe (für Karten/Kacheln)
            '--color-widget-primary-bg'      => '#f2f7f4',
            '--color-widget-success-bg'      => '#f0fdf4',
            '--color-widget-accent-bg'       => '#fffbeb', // Amber-50
            '--color-widget-warning-bg'      => '#fefce8',
            // Gemeinsam
            '--color-widget-header-text'     => '#ffffff',
            '--color-widget-body-bg'         => '#f2f7f4',
            // Losung spezifisch
            '--color-losung-header-from'     => '#4d7c5d',
            '--color-losung-header-to'       => '#2d4a36',
            '--color-losung-icon-bg'         => '#e6f0ea',
            '--color-losung-icon-color'      => '#4d7c5d',
            '--color-losung-icon2-bg'        => '#d1e8d8',
            '--color-losung-icon2-color'     => '#2d4a36',
            '--color-losung-outer-bg'        => 'linear-gradient(to bottom right, #f2f7f4, #e6f0ea)',
        ];
    }
}
