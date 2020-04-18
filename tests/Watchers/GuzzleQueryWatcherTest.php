<?php

namespace Janomr\Telescope\Tests\Watchers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Janomr\Telescope\Tests\FeatureTestCase;
use Janomr\Telescope\Watchers\GuzzleQueryWatcher;
use Laravel\Telescope\EntryType;

class GuzzleQueryWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            GuzzleQueryWatcher::class => true,
        ]);
    }

    public function test_guzzle_query_watcher_registers_database_queries()
    {
        $this->app->get('config')->set('guzzle', ['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], 'Example')
        ]))]);

        $this->app->make(Client::class)->get('http://example.com');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::QUERY, $entry->type);
        $this->assertSame('http://example.com', $entry->content['sql']);
        $this->assertSame('guzzle', $entry->content['connection']);
        $this->assertFalse($entry->content['slow']);
    }

    public function test_guzzle_query_watcher_can_tag_slow_queries()
    {

        $this->app->get('config')->set('guzzle', ['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], 'Example')
        ])), 'transfer_time' => 4.2]);

        $this->app->make(Client::class)->get('http://example.com');



        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::QUERY, $entry->type);
        $this->assertSame('http://example.com', $entry->content['sql']);
        $this->assertSame('guzzle', $entry->content['connection']);
        $this->assertGreaterThan(3000, intval($entry->content['time']));
        $this->assertTrue($entry->content['slow']);
    }
}
