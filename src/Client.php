<?php

namespace BugSquid;

use BugSquid\Transport\CurlTransport;
use BugSquid\Transport\NullTransport;
use BugSquid\Transport\Transport;

final class Client
{
    private Transport $transport;
    private array $buffer = [];
    private bool $capturing = false;

    public function __construct(
        private readonly Config $config,
        ?Transport $transport = null,
    ) {
        $this->transport = $transport ?? (
            $config->ingestKey === ''
                ? new NullTransport()
                : new CurlTransport($config)
        );
    }

    public function captureException(\Throwable $e, array $extra = []): void
    {
        if ($this->capturing) {
            return;
        }

        try {
            $this->capturing = true;
            $this->buffer[]  = (new PayloadBuilder())->build($e, $this->config, $extra);
        } catch (\Throwable) {
            // Silent — capture must never crash the host app.
        } finally {
            $this->capturing = false;
        }
    }

    public function flush(): void
    {
        $payloads     = $this->buffer;
        $this->buffer = [];

        foreach ($payloads as $payload) {
            try {
                $this->transport->send($payload);
            } catch (\Throwable) {
                // Silent — a broken transport must never surface to the caller.
            }
        }
    }

    public function register(): void
    {
        register_shutdown_function(function () {
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            $this->flush();
        });
    }
}
