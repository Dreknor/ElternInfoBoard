<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Poll_Answers extends Model
{
    protected $table = 'poll_answers';

    protected $fillable = ['poll_id', 'option_id'];

    public function option()
    {
        return $this->belongsTo(Poll_Option::class, 'option_id');
    }
}
