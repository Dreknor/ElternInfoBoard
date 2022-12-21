<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poll_Votes extends Model
{
    use HasFactory;

    protected $table = 'votes';

    protected $fillable = ['poll_id', 'author_id'];
}
