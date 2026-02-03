<?php

namespace App\Model;

use Artisanry\Commentable\Traits\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Discussion extends Model implements HasMedia
{
    use HasComments;
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = ['header', 'text', 'owner', 'sticky'];

    protected function casts(): array
    {
        return [
            'sticky' => 'boolean',
        ];
    }

    public function author(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'owner');
    }
}
