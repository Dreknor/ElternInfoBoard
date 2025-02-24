<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $table = 'settings_modules';

    protected $fillable = ['setting', 'category', 'description', 'options'];

    protected $casts = [
        'options' => 'array',
    ];
}
