<?php

namespace BugSquid\Tests\Support;

use BugSquid\Transport\Transport;

final class SpyTransport implements Transport
{
    /** @var array[] */
    public array $sent = [];

    public function send(array $payload): void
    {
        $this->sent[] = $payload;
    }
}
