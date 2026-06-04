<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AbfrageAntworten extends Model
{
    use HasFactory;
    use SoftDeletes;

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
