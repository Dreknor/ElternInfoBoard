<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteBlock extends Model
{
    use SoftDeletes;

    protected $fillable = ['site_id', 'block_id', 'block_type', 'position', 'title'];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function block()
    {
        return $this->morphTo();
    }
}
