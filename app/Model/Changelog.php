<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    protected $fillable = ['header', 'text', 'changeSettings'];

    protected $visible = ['header', 'text', 'changeSettings'];

    protected $casts = [
        'changeSettings' => 'boolean',
    ];
}
