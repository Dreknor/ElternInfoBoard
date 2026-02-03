<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Arbeitsgemeinschaft extends Model
{
    protected $table = 'arbeitsgemeinschaften';

    protected $fillable = [
        'name',
        'description',
        'weekday',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'max_participants',
        'manager_id',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'arbeitsgemeinschaften_groups', 'ag_id', 'group_id');
    }

    public function participants()
    {
        return $this->belongsToMany(Child::class, 'arbeitsgemeinschaften_participants', 'ag_id', 'participant_id');

    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
