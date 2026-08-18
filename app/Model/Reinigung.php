<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reinigung extends Model
{
    use HasFactory;

    /**
     * Pseudo-Bereich, der verwendet wird, wenn der Reinigungsplan gemäß
     * ReinigungSetting::$separate_bereiche als gemeinsamer Plan für die gesamte
     * Einrichtung geführt wird (oder wenn bei den Gruppen keine Bereiche gepflegt sind).
     */
    public const BEREICH_GESAMT = 'Gesamt';

    protected $table = 'reinigung';

    protected $visible = ['bereich', 'aufgabe', 'datum', 'bemerkung'];

    protected $fillable = ['bereich', 'aufgabe', 'datum', 'bemerkung', 'users_id'];

    public function getDatumAttribute($value): bool|Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id', 'id');
    }
}
