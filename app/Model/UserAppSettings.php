<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAppSettings extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_app_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Get the user that owns the settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the settings value by path using dot notation.
     *
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function getSettingByPath(string $path, $default = null)
    {
        return data_get($this->settings, $path, $default);
    }

    /**
     * Set the settings value by path using dot notation.
     *
     * @param string $path
     * @param mixed $value
     * @return void
     */
    public function setSettingByPath(string $path, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $path, $value);
        $this->settings = $settings;
    }
}
