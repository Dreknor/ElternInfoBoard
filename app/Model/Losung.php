<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Losung extends Model
{
    use HasFactory;

    protected $table = 'losungen';
    protected $fillable = ['date', 'Losungsvers', 'Losungstext', 'Lehrtextvers', 'Lehrtext'];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function getDateAttribute($value)
    {
        return ($value != '') ? $value : Carbon::now();
    }
}
