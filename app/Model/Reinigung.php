<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reinigung extends Model
{
    use HasFactory;

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
