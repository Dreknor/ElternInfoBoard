<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Rueckmeldungen extends Model
{
    protected $table = "rueckmeldungen";

    protected $fillable = ['posts_id', 'empfaenger', 'ende', 'text'];
    protected $visible = ['posts_id', 'empfaenger', 'ende', 'text'];

    protected $dates = ['created_at', 'updated_at', 'ende'];

    public function post(){
        return $this->belongsTo(Posts::class);
    }
}
