<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VertretungsplanWeek extends Model
{
    protected $table = 'vertretungsplan_weeks';
    protected $fillable = ['week', 'type'];

    protected $casts = [
        'week' => 'date',
    ];
}
