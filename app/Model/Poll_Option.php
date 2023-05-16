<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poll_Option extends Model
{
    use HasFactory;

    protected $table = 'poll_options';

    protected $fillable = ['poll_id', 'option'];

    protected $visible = ['option'];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class, 'poll_id');
    }
}
