<?php

namespace App\Model;

use App\Traits\NotificationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    use HasFactory;
    use NotificationTrait;

    protected $fillable = ['header', 'text', 'changeSettings'];

    protected $visible = ['header', 'text', 'changeSettings'];

    protected $casts = [
        'changeSettings' => 'boolean',
    ];
}
