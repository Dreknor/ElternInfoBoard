<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbfrageAntworten extends Model
{

    protected $table = 'abfrage_answers';

    protected $fillable = [
        'rueckmeldung_id', 'user_id', 'option_id', 'answer',
    ];

    protected $visible = ['answer'];

    public function option(): BelongsTo
    {
        return $this->belongsTo(AbfrageOptions::class, 'option_id');
    }
}
