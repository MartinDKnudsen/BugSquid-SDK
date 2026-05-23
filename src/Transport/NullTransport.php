<?php

namespace BugSquid\Transport;

final class NullTransport implements Transport
{
    public function send(array $payload): void {}
}
