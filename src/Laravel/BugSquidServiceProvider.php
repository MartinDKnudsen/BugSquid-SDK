<?php

namespace BugSquid\Laravel;

use BugSquid\Client;
use BugSquid\Config;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\ServiceProvider;

final class BugSquidServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/bugsquid.php', 'bugsquid');

        $this->app->singleton(Client::class, function ($app) {
            $cfg = $app['config']->get('bugsquid', []);

            return new Client(Config::fromArray([
                'endpoint'    => $cfg['endpoint']    ?? '',
                'ingest_key'  => $cfg['key']         ?? '',
                'environment' => $cfg['environment'] ?? 'production',
                'release'     => $cfg['release']     ?? null,
                'server_name' => $cfg['server_name'] ?? null,
            ]));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/bugsquid.php' => config_path('bugsquid.php'),
        ], 'bugsquid-config');

        $client = $this->app->make(Client::class);
        $client->register();

        $this->app['events']->listen(MessageLogged::class, function (MessageLogged $event) use ($client): void {
            $exception = $event->context['exception'] ?? null;

            if (!$exception instanceof \Throwable) {
                return;
            }

            $extra = [];

            // Enrich with Laravel request context when in an HTTP request.
            if (!$this->app->runningInConsole()) {
                try {
                    $req = $this->app->make('request');
                    $extra['extra'] = [
                        'url'     => $req->fullUrl(),
                        'method'  => $req->method(),
                        'headers' => $req->headers->all(),
                    ];
                } catch (\Throwable) {}
            }

            // Enrich with authenticated user identity.
            if ($this->app->bound('auth')) {
                try {
                    $user = $this->app->make('auth')->user();

                    if ($user !== null) {
                        $extra['user'] = array_filter([
                            'id'    => $user->getAuthIdentifier(),
                            'email' => $user->email ?? null,
                        ]);
                    }
                } catch (\Throwable) {}
            }

            $client->captureException($exception, $extra);
        });
    }
}
