<?php

namespace App\Providers;

use App\Model\Post;
use App\Model\Vertretung;
use App\Observers\PostObserver;
use App\Observers\VertretungObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 *
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        Post::observe(PostObserver::class);
        Vertretung::observe(VertretungObserver::class);

        Schema::defaultStringLength(191);

        setlocale(LC_TIME, 'de_DE');
        Carbon::setLocale('de_DE');

        //ModelEventObserver

        /**
         * Paginate a standard Laravel Collection.
         *
         * @param  int  $perPage
         * @param  int  $total
         * @param  int  $page
         * @param  string  $pageName
         * @return array
         */
        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });


        /**
         * @param array|string $attributes
         * @param string $searchTerm
         * @return Builder $query
         */
        Builder::macro( 'whereLike', function ($attributes, string $searchTerm) {
            $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach (Arr::wrap($attributes) as $attribute) {
                    $query->when(
                        Str::contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $searchTerm) {
                            [$relationName, $relationAttribute] = explode('.', $attribute);

                            $query->orWhereHas($relationName, function (Builder $query) use ($relationAttribute, $searchTerm) {
                                $query->where($relationAttribute, 'LIKE', "%{$searchTerm}%");
                            });
                        },
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                        }
                    );
                }
            });

            return $this;
        });

        /**
         * @param array|string $attributes
         * @param string $searchTerm
         * @return Builder $query
         */
        Builder::macro('orWhereLike', function ($attributes, string $searchTerm) {
            $this->orWhere(function (Builder $query) use ($attributes, $searchTerm) {
                foreach (Arr::wrap($attributes) as $attribute) {
                    $query->when(
                        Str::contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $searchTerm) {
                            [$relationName, $relationAttribute] = explode('.', $attribute);

                            $query->orWhereHas($relationName, function (Builder $query) use ($relationAttribute, $searchTerm) {
                                $query->where($relationAttribute, 'LIKE', "%{$searchTerm}%");
                            });
                        },
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                        }
                    );
                }
            });

            return $this;
        });

        Collection::macro('sortByDate', function ($column = 'created_at', $order = SORT_DESC) {
            /* @var $this Collection */
            return $this->sortBy(fn($datum) => strtotime($datum->$column), SORT_REGULAR, $order == SORT_DESC);
        });

        if (! Collection::hasMacro('sortByMulti')) {
            /**
             * An extension of the {@see Collection::sortBy()} method that allows for sorting against as many different
             * keys. Uses a combination of {@see Collection::sortBy()} and {@see Collection::groupBy()} to achieve this.
             *
             * @param  array  $keys An associative array that uses the key to sort by (which accepts dot separated values,
             *                    as {@see Collection::sortBy()} would) and the value is the order (either ASC or DESC)
             */
            Collection::macro('sortByMulti', function (array $keys) {
                $currentIndex = 0;
                $keys = array_map(function ($key, $sort) {
                    return ['key' => $key, 'sort' => $sort];
                }, array_keys($keys), $keys);

                $sortBy = function (Collection $collection) use (&$currentIndex, $keys, &$sortBy) {
                    if ($currentIndex >= count($keys)) {
                        return $collection;
                    }

                    $key = $keys[$currentIndex]['key'];
                    $sort = $keys[$currentIndex]['sort'];
                    $sortFunc = $sort === 'DESC' ? 'sortByDesc' : 'sortBy';
                    $currentIndex++;

                    return $collection->$sortFunc($key)->groupBy($key)->map($sortBy)->ungroup();
                };

                return $sortBy($this);
            });
        }
    }
}
