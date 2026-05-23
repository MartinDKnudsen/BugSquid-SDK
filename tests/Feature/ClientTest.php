<?php

use BugSquid\Client;
use BugSquid\Config;
use BugSquid\Transport\NullTransport;
use BugSquid\Transport\Transport;

function clientConfig(string $ingestKey = 'test-key'): Config
{
    return Config::fromArray([
        'endpoint'    => 'https://example.com/ingest',
        'ingest_key'  => $ingestKey,
        'environment' => 'testing',
        'server_name' => 'test-server',
    ]);
}

function spyTransport(): object
{
    return new class implements Transport {
        public array $sent = [];

        public function send(array $payload): void
        {
            $this->sent[] = $payload;
        }
    };
}

it('captureException then flush sends exactly one payload to the transport', function () {
    $spy    = spyTransport();
    $client = new Client(clientConfig(), $spy);

    $client->captureException(new \RuntimeException('boom'));
    expect($spy->sent)->toBeEmpty(); // buffered, not sent yet

    $client->flush();
    expect($spy->sent)->toHaveCount(1);

    $payload = $spy->sent[0];
    expect($payload['exception_class'])->toBe(\RuntimeException::class)
        ->and($payload['message'])->toBe('boom')
        ->and($payload['level'])->toBe('error');
});

it('a client with an empty ingest key uses NullTransport and sends nothing', function () {
    $client = new Client(clientConfig(''));

    $client->captureException(new \RuntimeException('silent'));
    $client->flush();

    // Verify NullTransport was auto-selected (no explicit transport injected).
    $ref = new \ReflectionProperty(Client::class, 'transport');
    $ref->setAccessible(true);
    expect($ref->getValue($client))->toBeInstanceOf(NullTransport::class);
});

it('flush completes normally even when the transport throws', function () {
    $throwing = new class implements Transport {
        public bool $called = false;

        public function send(array $payload): void
        {
            $this->called = true;
            throw new \RuntimeException('transport exploded');
        }
    };

    $client = new Client(clientConfig(), $throwing);
    $client->captureException(new \LogicException('some error'));

    // Must not throw.
    expect(fn() => $client->flush())->not->toThrow(\Throwable::class);
    expect($throwing->called)->toBeTrue();
});
