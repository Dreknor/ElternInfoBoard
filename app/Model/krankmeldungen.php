<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class krankmeldungen extends Model
{
    use SoftDeletes;

    protected $table = 'krankmeldungen';

    protected $fillable = ['name','kommentar', 'start', 'ende', 'users_id' ];
    protected $visible = ['name','kommentar', 'start', 'ende'];

    public function user(){
        return $this->belongsTo(User::class, 'users_id');
    }

    protected $dates = ['created_at', 'updated_at', 'start', 'ende'];
}
