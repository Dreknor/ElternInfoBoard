<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteBlock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['site_id', 'block_id', 'block_type', 'position', 'title'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function block(): MorphTo
    {
        return $this->morphTo();
    }
}
