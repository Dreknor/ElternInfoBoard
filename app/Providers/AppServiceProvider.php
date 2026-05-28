<?php

namespace App\Providers;

use App\Model\PersonalAccessToken;
use App\Settings\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Model\Conversation;
use App\Model\Liste;
use App\Policies\ConversationPolicy;
use App\Policies\TerminListenPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(GeneralSetting $settings): void
    {
        // Policy-Registrierung
        Gate::policy(Liste::class, TerminListenPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);

        // Use custom PersonalAccessToken model with explicit MySQL connection
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Paginator::useBootstrap();

        View::share('settings', $settings);

        Schema::defaultStringLength(191);

        // Set Laravel locale
        app()->setLocale('de');

        setlocale(LC_TIME, 'de_DE');
        Carbon::setLocale('de_DE');

        // ModelEventObserver

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
         * @param  array|string  $attributes
         * @param  string  $searchTerm
         * @return Builder $query
         */
        Builder::macro('whereLike', function ($attributes, string $searchTerm) {
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
         * @param  array|string  $attributes
         * @param  string  $searchTerm
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
            return $this->sortBy(fn ($datum) => strtotime($datum->$column), SORT_REGULAR, $order == SORT_DESC);
        });

        if (! Collection::hasMacro('sortByMulti')) {
            /**
             * An extension of the {@see Collection::sortBy()} method that allows for sorting against as many different
             * keys. Uses a combination of {@see Collection::sortBy()} and {@see Collection::groupBy()} to achieve this.
             *
             * @param  array  $keys  An associative array that uses the key to sort by (which accepts dot separated values,
             *                       as {@see Collection::sortBy()} would) and the value is the order (either ASC or DESC)
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

        $this->bootRoute();
    }

    public function bootRoute()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(80)->by($request->user()?->id ?: $request->ip());
        });

        // Dediziertes, strenges Rate-Limit für externe API-Key-Endpunkte (Vertretungsplan, Stundenplan).
        // Verhindert Brute-Force-Angriffe auf den API-Key.
        RateLimiter::for('external-api', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Rate-Limit für Login-Endpunkt (Magic-Link-Anfragen + Passwort-Login).
        // Verhindert E-Mail-Bombing und Brute-Force auf Passwörter.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        Route::model('event', \App\Model\ElternratEvent::class);
        Route::model('task', \App\Model\ElternratTask::class);

    }
}
