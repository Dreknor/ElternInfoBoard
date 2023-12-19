<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'reporting', 'wiederzulassung_durch', 'wiederzulassung_wann', 'aushang_dauer'
    ];
    protected $visible = [
        'name', 'reporting', 'wiederzulassung_durch', 'wiederzulassung_wann', 'aushang_dauer', 'id'
    ];

    public function activeDiseases()
    {
        return $this->hasMany(ActiveDisease::class);
    }


}
