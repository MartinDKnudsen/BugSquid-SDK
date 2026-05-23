<?php

namespace BugSquid\Transport;

use BugSquid\Config;

final class CurlTransport implements Transport
{
    public function __construct(private readonly Config $config) {}

    public function send(array $payload): void
    {
        try {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL               => $this->config->endpoint,
                CURLOPT_POST              => true,
                CURLOPT_POSTFIELDS        => json_encode($payload),
                CURLOPT_HTTPHEADER        => [
                    'Content-Type: application/json',
                    'X-Ingest-Key: ' . $this->config->ingestKey,
                ],
                CURLOPT_CONNECTTIMEOUT_MS => 1000,
                CURLOPT_TIMEOUT_MS        => 2000,
                CURLOPT_RETURNTRANSFER    => true,
            ]);

            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable) {
            // Silent — sending must never throw or hang the host app.
        }
    }
}
