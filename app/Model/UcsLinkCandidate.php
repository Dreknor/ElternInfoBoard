<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Initial-Linking-Kandidat für den UCS@school-Sync-Workflow.
 *
 * Wenn beim Sync ein lokales Kind (ucs_source='local') gefunden wird, dessen
 * Vor-/Nachname + Klasse zu einem UCS-Kind passt, wird KEIN Duplikat in children
 * angelegt, sondern ein Eintrag in dieser Tabelle erstellt.
 * Nach Admin-Bestätigung (UI oder `ucs:link-child`) werden die Datensätze verschmolzen.
 *
 * @property int         $id
 * @property int         $child_id
 * @property string      $ucs_username
 * @property string|null $ucs_uuid
 * @property string      $reason          'name_match' | 'manual'
 * @property array|null  $payload         Original-Kelvin-Daten zum Review
 * @property \Carbon\Carbon $detected_at
 * @property \Carbon\Carbon|null $confirmed_at
 * @property int|null    $confirmed_by
 *
 * @see docs/ucs-kelvin-integration-konzept.md §5.2 / §8 / §15.1
 */
class UcsLinkCandidate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'child_id',
        'ucs_username',
        'ucs_uuid',
        'reason',
        'payload',
        'detected_at',
        'confirmed_at',
        'confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'detected_at'  => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Nur noch nicht bestätigte Kandidaten.
     */
    public function scopePending($query)
    {
        return $query->whereNull('confirmed_at');
    }

    /**
     * Bereits bestätigte Kandidaten.
     */
    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('confirmed_at');
    }
}

