<?php

namespace App\Jobs;

use App\Services\Push\NativePushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNativePush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public array $userIds,
        public string $title,
        public string $message,
        public ?string $url,
        public string $type,
    ) {}

    public function handle(NativePushService $service): void
    {
        $service->send($this->userIds, $this->title, $this->message, $this->url, $this->type);
    }
}
