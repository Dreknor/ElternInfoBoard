<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $fillable = ['setting', 'category', 'description', 'options'];

    protected $casts = [
        'options' => 'array',
    ];
}
