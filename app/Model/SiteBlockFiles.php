<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteBlockFiles extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;


    protected $table = 'sites_blocks_files';
}
