<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbfrageAntworten extends Model
{
    use HasFactory;

    protected $table = 'abfrage_answers';
    protected $fillable = [
        'rueckmeldung_id', 'user_id', 'option_id', 'answer'
    ];

    protected $visible = ['answer'];

    public function option()
    {
        return $this->belongsTo(AbfrageOptions::class, 'option_id');
    }

}
