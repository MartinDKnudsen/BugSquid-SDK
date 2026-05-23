<?php

namespace BugSquid\Transport;

interface Transport
{
    public function send(array $payload): void;
}
