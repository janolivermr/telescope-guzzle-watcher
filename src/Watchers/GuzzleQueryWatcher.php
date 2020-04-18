<?php

namespace Janomr\Telescope\Watchers;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\FetchesStackTrace;
use Laravel\Telescope\Watchers\Watcher;

class GuzzleQueryWatcher extends Watcher
{
    use FetchesStackTrace;

    /**
     * @inheritDoc
     */
    public function register($app)
    {
        $app->bind(Client::class, function ($app) {
            $config = $app['config']['guzzle'] ?? [];
            if (Telescope::isRecording()) {
                $config['on_stats'] = function (TransferStats $stats) {
                    $caller = $this->getCallerFromStackTrace();
                    Telescope::recordQuery(IncomingEntry::make([
                        'connection' => 'guzzle',
                        'bindings' => [],
                        'sql' => (string)$stats->getEffectiveUri(),
                        'time' => number_format($stats->getTransferTime()*1000, 2, '.', ''),
                        'slow' => $stats->getTransferTime() > 1,
                        'file' => $caller['file'],
                        'line' => $caller['line'],
                        'hash' => md5((string)$stats->getEffectiveUri()),
                    ]));
                };
            }
            return new Client($config);
        });
    }
}
