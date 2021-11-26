<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poll extends Model
{
    use SoftDeletes;

    protected $fillable = ['poll_name', 'description', 'ends', 'post_id', 'author_id', 'max_number'];


    protected $casts = [
        'ends' => 'date'
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function options()
    {
        return $this->hasMany(Poll_Option::class, 'poll_id');
    }

    public function votes()
    {
        return $this->hasMany(Poll_Votes::class, 'poll_id');
    }

    public function answers()
    {
        return $this->hasMany(Poll_Answers::class, 'poll_id');
    }

}
