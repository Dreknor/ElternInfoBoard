<?php

namespace App\Themes;

use App\Themes\Contracts\ThemeInterface;

abstract class AbstractTheme implements ThemeInterface
{
    public function previewImage(): ?string
    {
        return null;
    }

    public function bodyClasses(): string
    {
        return '';
    }
}

