<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    use HasFactory;

    protected $fillable = ['header', 'text', 'changeSettings'];

    protected $visible = ['header', 'text', 'changeSettings'];

    protected $casts = [
        'changeSettings' => 'boolean',
    ];
}
