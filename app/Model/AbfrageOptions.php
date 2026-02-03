<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbfrageOptions extends Model
{
    use HasFactory;

    protected $fillable = ['rueckmeldung_id', 'type', 'option', 'required'];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AbfrageAntworten::class, 'option_id');
    }
}
