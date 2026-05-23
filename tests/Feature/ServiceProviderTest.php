<?php

use BugSquid\Client;
use BugSquid\Tests\Support\SpyTransport;
use Illuminate\Log\Events\MessageLogged;

// Helper: replace the private transport on an existing Client via reflection.
function injectSpy(Client $client): SpyTransport
{
    $spy  = new SpyTransport();
    $prop = new \ReflectionProperty(Client::class, 'transport');
    $prop->setAccessible(true);
    $prop->setValue($client, $spy);

    return $spy;
}

it('the service provider binds Client as a singleton', function () {
    $a = $this->app->make(Client::class);
    $b = $this->app->make(Client::class);

    expect($a)->toBeInstanceOf(Client::class)
        ->and($a)->toBe($b);
});

it('a MessageLogged event carrying a Throwable in context triggers a capture', function () {
    $client = $this->app->make(Client::class);
    $spy    = injectSpy($client);

    $this->app['events']->dispatch(new MessageLogged('error', 'unhandled error', [
        'exception' => new \RuntimeException('something broke'),
    ]));

    $client->flush();

    expect($spy->sent)->toHaveCount(1)
        ->and($spy->sent[0]['exception_class'])->toBe(\RuntimeException::class)
        ->and($spy->sent[0]['message'])->toBe('something broke');
});

it('a MessageLogged event with no exception in context does not capture', function () {
    $client = $this->app->make(Client::class);
    $spy    = injectSpy($client);

    $this->app['events']->dispatch(new MessageLogged('error', 'just a log line', []));
    $this->app['events']->dispatch(new MessageLogged('warning', 'also no exception', [
        'exception' => 'not a Throwable — just a string',
    ]));

    $client->flush();

    expect($spy->sent)->toBeEmpty();
});
