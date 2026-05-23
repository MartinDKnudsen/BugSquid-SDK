<?php

use BugSquid\Scrubber;

it('replaces a sensitive key value with [Filtered]', function () {
    $result = (new Scrubber())->scrub(['password' => 'secret123', 'name' => 'Martin'], ['password']);

    expect($result['password'])->toBe('[Filtered]')
        ->and($result['name'])->toBe('Martin');
});

it('matches keys case-insensitively', function () {
    $result = (new Scrubber())->scrub(['Authorization' => 'Bearer tok'], ['authorization']);

    expect($result['Authorization'])->toBe('[Filtered]');
});

it('scrubs sensitive keys at any depth', function () {
    $data = [
        'request' => [
            'headers' => ['Authorization' => 'Bearer tok', 'X-Request-Id' => 'abc'],
            'body'    => ['password' => 'hunter2', 'username' => 'martin'],
        ],
    ];

    $result = (new Scrubber())->scrub($data, ['authorization', 'password']);

    expect($result['request']['headers']['Authorization'])->toBe('[Filtered]')
        ->and($result['request']['body']['password'])->toBe('[Filtered]')
        ->and($result['request']['headers']['X-Request-Id'])->toBe('abc')
        ->and($result['request']['body']['username'])->toBe('martin');
});

it('leaves non-sensitive header values untouched', function () {
    $result = (new Scrubber())->scrub(
        ['Content-Type' => 'application/json', 'X-Request-Id' => 'abc-123'],
        ['password', 'authorization', 'token'],
    );

    expect($result['Content-Type'])->toBe('application/json')
        ->and($result['X-Request-Id'])->toBe('abc-123');
});
