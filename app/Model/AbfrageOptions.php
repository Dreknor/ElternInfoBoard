<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AbfrageOptions extends Model
{
    use HasFactory;
    use SoftDeletes;

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
