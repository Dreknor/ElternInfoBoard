<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use Illuminate\Support\Facades\Log;
use Throwable;
use Tests\TestCase;

class ExceptionLoggingTest extends TestCase
{
    public function test_report_logs_reportable_exceptions_to_application_logs(): void
    {
        Log::spy();

        /** @var Handler $handler */
        $handler = $this->app->make(Handler::class);
        $exception = new \RuntimeException('Testfehler');

        $handler->report($exception);

        Log::shouldHaveReceived('error')->once()->with(
            'Unbehandelte Ausnahme',
            \Mockery::on(function (array $context) use ($exception) {
                return $context['exception'] === $exception::class
                    && $context['message'] === 'Testfehler';
            })
        );
    }
}
