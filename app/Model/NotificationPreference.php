<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Gewählte Benachrichtigungskanäle eines Nutzers für eine Kategorie
 * (siehe App\Services\Notifications\NotificationCategory).
 */
class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'category', 'app', 'web', 'mail'];

    protected $casts = [
        'app' => 'boolean',
        'web' => 'boolean',
        'mail' => 'boolean',
    ];
}
