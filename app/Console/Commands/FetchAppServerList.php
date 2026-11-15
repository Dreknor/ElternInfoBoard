<?php

namespace App\Console\Commands;

use App\Services\App\AppServerList;
use Illuminate\Console\Command;

class FetchAppServerList extends Command
{
    protected $signature = 'app:server-list';

    protected $description = 'Holt die signierte Freigabeliste der ElternInfo-App vom Herausgeber (APP_SERVER_LIST_URL).';

    public function handle(AppServerList $list): int
    {
        if (! config('services.app.server_list_url')) {
            $this->warn('APP_SERVER_LIST_URL ist nicht gesetzt – nichts zu tun.');

            return self::SUCCESS;
        }

        if (! $list->fetch()) {
            $this->error('Die Freigabeliste konnte nicht übernommen werden (Details im Log).');

            return self::FAILURE;
        }

        $status = $list->statusForThisServer();
        $this->info('Freigabeliste übernommen. Status dieses Servers: '.$status['state'].'.');

        return self::SUCCESS;
    }
}
