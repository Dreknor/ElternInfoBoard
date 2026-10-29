<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $table = 'settings_modules';

    protected $fillable = ['setting', 'category', 'description', 'options', 'sort_order'];

    /**
     * Für diese Instanz gesperrte Module (`config/modules.php`):
     * eingeschränkt und nicht per MODULES_UNLOCKED freigeschaltet.
     *
     * @return string[]
     */
    public static function lockedSettings(): array
    {
        return array_values(array_diff(
            config('modules.restricted', []),
            config('modules.unlocked', [])
        ));
    }

    public static function isLockedSetting(string $setting): bool
    {
        return in_array($setting, self::lockedSettings(), true);
    }

    public function isLocked(): bool
    {
        return self::isLockedSetting((string) $this->setting);
    }

    /** Nur Module, die auf dieser Instanz verfügbar sind. */
    public function scopeAvailable(Builder $query): Builder
    {
        $locked = self::lockedSettings();

        return empty($locked) ? $query : $query->whereNotIn('setting', $locked);
    }

    /**
     * Gesperrte Module gelten immer als inaktiv, unabhängig vom gespeicherten Wert.
     * So greift die Sperre auch an allen Stellen, die `options['active']` direkt lesen.
     */
    protected function options(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $options = is_string($value) ? json_decode($value, true) : $value;

                if (is_array($options) && $this->isLocked()) {
                    $options['active'] = '0';
                }

                return $options;
            },
            set: fn ($value) => is_null($value) ? null : json_encode($value),
        );
    }
}
