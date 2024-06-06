<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = ['type', 'user_id', 'title', 'message', 'icon', 'url', 'read', 'important'];

    protected $visible = ['id','type', 'user_id', 'title', 'message', 'icon', 'url', 'read', 'important'];

    protected $casts = [
        'read' => 'boolean',
        'important' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }


}
