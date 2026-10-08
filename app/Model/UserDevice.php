<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gerät der Eltern-App mit nativem Push-Token (APNs oder FCM).
 */
class UserDevice extends Model
{
    public const PROVIDER_FCM = 'fcm';

    public const PROVIDER_APNS = 'apns';

    protected $fillable = ['user_id', 'token', 'provider', 'platform', 'device_name', 'app_version', 'last_seen_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
