<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Symfony\Component\{HttpKernel\Exception\HttpException,
    HttpKernel\Exception\NotFoundHttpException,
    Routing\Exception\MethodNotAllowedException};
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception) && app()->environment('production')) {
            //app('sentry')->captureException($exception);
        }

        Log::info($exception->getMessage());
        Log::info(Request::fullUrl());

        parent::report($exception);
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
        $url = Request::fullUrl();

        Log::info("404 in {$url}");
        }

        if ($exception instanceof HttpException && $exception->getStatusCode() == 302) {
            Log::info("302 in {$url}");
        }

        if ($exception instanceof MethodNotAllowedException) {
            Log::info("405 in {$url}");
        }

        // Convert all non-http exceptions to a proper 500 http exception
        // if we don't do this exceptions are shown as a default template
        // instead of our own view in resources/views/errors/500.blade.php
        if ($this->shouldReport($exception) && ! $this->isHttpException($exception) && ! config('app.debug')) {
            $exception = new HttpException(500, 'Whoops!');
        }



        return parent::render($request, $exception);
    }
}
