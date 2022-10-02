<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Poll_Option extends Model
{
    protected $table = 'poll_options';

    protected $fillable = ['poll_id', 'option'];

    protected $visible = ['option'];

    public function poll()
    {
        return $this->belongsTo(Poll::class, 'poll_id');
    }
}
