<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VertretungsplanAbsence extends Model
{

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'reason',
        'absence_id',
    ];

    protected $table = 'vertretungsplan_absences';

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
