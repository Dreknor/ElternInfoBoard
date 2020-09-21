<?php

namespace App\Model;

use Benjivm\Commentable\Traits\HasComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

class Discussion extends Model
{
    use SoftDeletes;
    use HasMediaTrait;
    use HasComments;

    protected $fillable = ['header', 'text', 'owner', 'sticky'];
    protected $casts = [
        'sticky'    => 'boolean'
    ];

    public function author(){
        return $this->hasOne(User::class, 'id', 'owner');
    }

}
