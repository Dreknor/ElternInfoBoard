<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SortGroupsScope implements \Illuminate\Database\Eloquent\Scope
{
    /**
     * {@inheritdoc}
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->orderBy('name');
    }
}
