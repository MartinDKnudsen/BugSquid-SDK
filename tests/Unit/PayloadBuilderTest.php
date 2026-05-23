<?php

use BugSquid\Config;
use BugSquid\PayloadBuilder;

function makeConfig(array $overrides = []): Config
{
    return Config::fromArray(array_merge([
        'endpoint'    => 'https://example.com/ingest',
        'ingest_key'  => 'test-key',
        'server_name' => 'test-server',
        'environment' => 'testing',
    ], $overrides));
}

it('builds a correctly shaped payload for two exceptions differing only in numeric message content', function () {
    $builder = new PayloadBuilder();
    $config  = makeConfig();

    foreach (['Error code 1', 'Error code 2'] as $msg) {
        $payload = $builder->build(new \RuntimeException($msg), $config);

        expect($payload)->toHaveKeys([
            'level', 'exception_class', 'message', 'stacktrace',
            'environment', 'release', 'server_name', 'occurred_at',
            'request', 'context', 'url',
        ]);
        expect($payload['level'])->toBe('error');
        expect($payload['exception_class'])->toBe(\RuntimeException::class);
        expect($payload['message'])->toBe($msg);
        expect($payload['stacktrace'])->toBeArray()->not->toBeEmpty();
        expect($payload['context'])->toHaveKeys(['user', 'extra']);
    }
});

it('first stack frame is the throw site', function () {
    $e         = new \RuntimeException('test');
    $throwFile = $e->getFile();
    $throwLine = $e->getLine();

    $payload = (new PayloadBuilder())->build($e, makeConfig());

    expect($payload['stacktrace'][0]['file'])->toBe($throwFile)
        ->and($payload['stacktrace'][0]['line'])->toBe($throwLine);
});

it('frames under /vendor/ have in_app=false', function () {
    // Pest itself is in vendor, so deep traces always contain vendor frames.
    $payload = (new PayloadBuilder())->build(new \RuntimeException('test'), makeConfig());

    $vendorFrames = array_filter(
        $payload['stacktrace'],
        fn($f) => str_contains($f['file'], '/vendor/')
    );

    expect($vendorFrames)->not->toBeEmpty();

    foreach ($vendorFrames as $frame) {
        expect($frame['in_app'])->toBeFalse();
    }
});

it('a frame outside /vendor/ has in_app=true', function () {
    $e       = new \RuntimeException('test');
    $payload = (new PayloadBuilder())->build($e, makeConfig());

    // Throw site is this test file, which is not in vendor
    $first = $payload['stacktrace'][0];

    expect($first['in_app'])->toBeTrue()
        ->and($first['file'])->not->toContain('/vendor/');
});

it('request is null in a CLI environment', function () {
    $payload = (new PayloadBuilder())->build(new \RuntimeException('cli'), makeConfig());

    // Tests run under CLI — no REQUEST_METHOD in $_SERVER
    expect($payload['request'])->toBeNull();
});
