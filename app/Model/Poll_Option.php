<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poll_Option extends Model
{
    protected $table = 'poll_options';

    protected $fillable = ['poll_id', 'option'];

    protected $visible = ['option'];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class, 'poll_id');
    }
}
