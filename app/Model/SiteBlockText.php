<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteBlockText extends Model
{
    use SoftDeletes;

    protected $table = 'sites_blocks_text';

    protected $fillable = ['content'];

}
