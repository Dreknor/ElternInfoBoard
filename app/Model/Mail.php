<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Mail extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['senders_id', 'subject', 'text', 'to', 'file'];

    protected $visible = ['senders_id', 'subject', 'text', 'to', 'file'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'senders_id', 'id');
    }
}
