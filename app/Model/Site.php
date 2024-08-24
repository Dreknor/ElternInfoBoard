<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

class Site extends Model
{
    protected $fillable = ['name', 'author_id', 'is_active'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'site_group');
    }

    public function getIsActiveAttribute($value)
    {
        return (bool) $value;
    }

    public function users(): HasManyDeep
    {
        return $this->hasManyDeep(User::class, ['site_group', Group::class, 'group_user']);
    }

    public function blocks()
    {
        return $this->hasMany(SiteBlock::class)->orderBy('position');
    }
}
