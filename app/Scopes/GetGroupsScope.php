<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class GetGroupsScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where(function ($query) {
            $query->where('owner_id', null)
                ->orWhere('owner_id', auth()->id())
                ->orWhereHas('users', function ($query) {
                    $query->where('user_id', auth()->id());
                });
        });
    }
}
