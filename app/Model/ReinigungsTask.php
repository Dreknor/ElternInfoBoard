<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReinigungsTask extends Model
{
    use HasFactory;

    protected $table = 'reinigungs_tasks';

    protected $fillable = ['task'];

    protected $visible = ['task'];
}
