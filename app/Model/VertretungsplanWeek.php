<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VertretungsplanWeek extends Model
{
    protected $table = 'vertretungsplan_weeks';

    protected $fillable = ['week', 'type'];

    protected function casts(): array
    {
        return [
            'week' => 'date',
        ];
    }
}
