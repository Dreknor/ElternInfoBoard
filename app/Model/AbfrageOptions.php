<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbfrageOptions extends Model
{
    use HasFactory;


    protected $fillable = ['rueckmeldung_id', 'type', 'option'];

    public function answers()
    {
        return $this->hasMany(AbfrageAntworten::class, 'option_id');
    }
}
