<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CareSetting extends Settings
{
    public bool $view_detailed_care;

    /** Ansicht der Anwesenheitsliste: simple | detailed | board */
    public string $view_mode;

    public bool $hide_childs_when_absent;

    public array $groups_list;

    public array $class_list;

    public bool $hide_groups_when_empty;

    public bool $show_message_on_empty_group;

    public ?string $end_time;

    public ?int $info_to;

    public bool $mandate_notification_enabled;

    public ?string $mandate_notification_email;

    public bool $show_mandates;

    public bool $show_parents;

    public string $bundesland;

    public bool $auto_checkin_enabled_schulzeit;

    public string $auto_checkin_time_schulzeit;

    public bool $auto_checkin_enabled_ferien;

    public string $auto_checkin_time_ferien;

    public const VIEW_MODES = ['simple', 'detailed', 'board'];

    /** Cookie mit der auf diesem Gerät gewählten Ansicht (überschreibt die Einstellung) */
    public const DEVICE_VIEW_COOKIE = 'attView';

    /**
     * In den Einstellungen festgelegte Ansicht; fällt auf die alte Ja/Nein-Einstellung zurück.
     */
    public function configuredViewMode(): string
    {
        return in_array($this->view_mode, self::VIEW_MODES, true)
            ? $this->view_mode
            : ($this->view_detailed_care ? 'detailed' : 'simple');
    }

    /**
     * Auf diesem Gerät gewählte Ansicht oder null, wenn die Einstellung gilt.
     */
    public function deviceViewMode(): ?string
    {
        $mode = app()->bound('request') ? request()->cookie(self::DEVICE_VIEW_COOKIE) : null;

        return in_array($mode, self::VIEW_MODES, true) ? $mode : null;
    }

    /**
     * Tatsächlich angezeigte Ansicht: Gerätewahl vor Einstellung.
     */
    public function viewMode(): string
    {
        return $this->deviceViewMode() ?? $this->configuredViewMode();
    }

    /**
     * Ansichten, die den Modus „Anwesende und erwartete Kinder“ anbieten.
     */
    public function supportsExpectedMode(): bool
    {
        return in_array($this->viewMode(), ['detailed', 'board'], true);
    }

    public static function group(): string
    {
        return 'care';
    }
}
