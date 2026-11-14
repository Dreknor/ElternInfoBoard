<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SecurityEvent
 *
 * Eintrag des Sicherheitsprotokolls (nur Datensicherheit, siehe config/security_log.php).
 * Bewusst nicht auditiert und ohne updated_at.
 */
class SecurityEvent extends Model
{
    public $timestamps = false;

    protected $table = 'security_events';

    protected $fillable = [
        'event',
        'user_id',
        'actor_id',
        'ip_address',
        'user_agent',
        'context',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    /** Betroffenes Konto */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    /** Handelnde Person (falls abweichend) */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withDefault();
    }
}
