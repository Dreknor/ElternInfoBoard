<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Poll_Votes extends Model
{
    protected $table = 'votes';

    protected $fillable = ['poll_id', 'author_id'];
}
