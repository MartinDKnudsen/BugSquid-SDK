<?php

use BugSquid\Config;

it('holds required config values with defaults', function () {
    $config = Config::fromArray([
        'endpoint'   => 'https://bugsquid.example.com/ingest',
        'ingest_key' => 'test-key-123',
    ]);

    expect($config->endpoint)->toBe('https://bugsquid.example.com/ingest')
        ->and($config->ingestKey)->toBe('test-key-123')
        ->and($config->environment)->toBe('production')
        ->and($config->release)->toBeNull()
        ->and($config->serverName)->toBe(gethostname() ?: null);
});

it('accepts optional config values', function () {
    $config = Config::fromArray([
        'endpoint'    => 'https://bugsquid.example.com/ingest',
        'ingest_key'  => 'test-key-123',
        'environment' => 'staging',
        'release'     => '1.2.3',
        'server_name' => 'web-1',
    ]);

    expect($config->environment)->toBe('staging')
        ->and($config->release)->toBe('1.2.3')
        ->and($config->serverName)->toBe('web-1');
});

it('has default scrub fields', function () {
    $config = Config::fromArray([
        'endpoint'   => 'https://bugsquid.example.com/ingest',
        'ingest_key' => 'test-key-123',
    ]);

    expect($config->scrubFields)
        ->toContain('password')
        ->toContain('authorization')
        ->toContain('token')
        ->toContain('api_key')
        ->toContain('secret');
});

it('accepts custom scrub fields', function () {
    $config = Config::fromArray([
        'endpoint'     => 'https://bugsquid.example.com/ingest',
        'ingest_key'   => 'test-key-123',
        'scrub_fields' => ['my_secret', 'credit_card'],
    ]);

    expect($config->scrubFields)->toBe(['my_secret', 'credit_card']);
});
