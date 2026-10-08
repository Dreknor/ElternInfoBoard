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
     * Offene Kandidaten: noch nicht bestätigt, nicht verworfen und
     * Kind noch vorhanden (nicht soft-deleted).
     *
     * Primär-Filter: confirmed_at IS NULL (reject() setzt confirmed_at immer).
     * Defensiv-Filter: payload->status != 'rejected' schützt vor manuellen
     * DB-Eingriffen, bei denen confirmed_at aus Versehen NULL geblieben ist.
     * Waisenschutz: whereHas('child') filtert Kandidaten heraus, deren Kind
     * soft-deleted wurde (cascadeOnDelete greift nur bei hard-delete!).
     *
     * @see docs/ucs-kelvin-integration-konzept.md §5.2 / §8
     */
    public function scopeOpen($query)
    {
        return $query
            ->whereNull('confirmed_at')
            ->where(function ($q) {
                $q->whereNull('payload->status')
                  ->orWhere('payload->status', '!=', 'rejected');
            })
            ->whereHas('child'); // Kind muss noch existieren (nicht soft-deleted)
    }

    /**
     * Alias für scopeOpen – noch nicht bestätigte Kandidaten (Kind vorhanden).
     */
    public function scopePending($query)
    {
        return $query
            ->whereNull('confirmed_at')
            ->where(function ($q) {
                $q->whereNull('payload->status')
                  ->orWhere('payload->status', '!=', 'rejected');
            })
            ->whereHas('child'); // Kind muss noch existieren (nicht soft-deleted)
    }

    /**
     * Bereits bestätigte oder verworfene Kandidaten.
     */
    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('confirmed_at');
    }

    /**
     * Verworfene Kandidaten (confirmed, payload.status = 'rejected').
     */
    public function scopeRejected($query)
    {
        return $query->whereNotNull('confirmed_at')
            ->whereJsonContains('payload->status', 'rejected');
    }

    /**
     * Gibt an, ob dieser Kandidat verworfen wurde.
     */
    public function isRejected(): bool
    {
        return ($this->payload['status'] ?? '') === 'rejected';
    }
}

