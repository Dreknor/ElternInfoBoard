<?php

namespace App\Themes;

use App\Settings\CustomThemeSetting;

class CustomTheme extends AbstractTheme
{
    private array $customVariables;

    private string $themeName;

    private string $themeDescription;

    public function __construct(CustomThemeSetting $setting)
    {
        $this->customVariables  = $setting->variables ?? [];
        $this->themeName        = $setting->name ?: 'Eigenes Design';
        $this->themeDescription = $setting->description ?: 'Individuell angepasstes Design';
    }

    public function id(): string
    {
        return 'custom';
    }

    public function name(): string
    {
        return $this->themeName;
    }

    public function description(): string
    {
        return $this->themeDescription;
    }

    public function previewImage(): ?string
    {
        return '/img/themes/preview-custom.svg';
    }

    public function variables(): array
    {
        // Basis: DefaultTheme als Fallback
        $base = (new DefaultTheme())->variables();

        // Benutzerdefinierte Werte überschreiben
        return array_merge($base, $this->customVariables);
    }
}


