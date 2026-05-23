<?php

namespace App\Themes\Contracts;

interface ThemeInterface
{
    /** Interner Identifier, z.B. 'default', 'dark', 'nature' */
    public function id(): string;

    /** Anzeigename für Admin/User UI */
    public function name(): string;

    /** Kurze Beschreibung */
    public function description(): string;

    /** Optional: Pfad zur Vorschau-Grafik (public/) */
    public function previewImage(): ?string;

    /** Alle CSS Custom Properties als key => value Array */
    public function variables(): array;

    /** Optionale Tailwind-Klassen die dynamisch auf <body> gesetzt werden */
    public function bodyClasses(): string;
}

