<?php

namespace BugSquid;

final class PayloadBuilder
{
    private Scrubber $scrubber;

    public function __construct()
    {
        $this->scrubber = new Scrubber();
    }

    public function build(\Throwable $e, Config $config, array $extra = []): array
    {
        $request = $this->buildRequest($config);
        $url     = $request['url'] ?? null;

        return [
            'level'           => 'error',
            'exception_class' => get_class($e),
            'message'         => $e->getMessage(),
            'stacktrace'      => $this->buildStacktrace($e),
            'request'         => $request,
            'context'         => $this->scrubber->scrub([
                'user'  => $extra['user'] ?? [],
                'extra' => $extra['extra'] ?? [],
            ], $config->scrubFields),
            'environment'     => $config->environment,
            'release'         => $config->release,
            'server_name'     => $config->serverName,
            'url'             => $url,
            'occurred_at'     => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
        ];
    }

    private function buildStacktrace(\Throwable $e): array
    {
        $trace  = $e->getTrace();
        $frames = [];

        // First frame: the throw site, function context from trace[0]
        $frames[] = [
            'file'     => $e->getFile(),
            'line'     => $e->getLine(),
            'function' => isset($trace[0]) ? $this->functionName($trace[0]) : '',
            'in_app'   => $this->isInApp($e->getFile()),
        ];

        foreach ($trace as $frame) {
            $frames[] = [
                'file'     => $frame['file'] ?? '',
                'line'     => $frame['line'] ?? 0,
                'function' => $this->functionName($frame),
                'in_app'   => $this->isInApp($frame['file'] ?? ''),
            ];
        }

        return $frames;
    }

    private function functionName(array $frame): string
    {
        $fn = $frame['function'] ?? '';

        if (isset($frame['class'], $frame['type'])) {
            return $frame['class'] . $frame['type'] . $fn;
        }

        return $fn;
    }

    private function isInApp(string $file): bool
    {
        return $file !== '' && !str_contains($file, '/vendor/');
    }

    private function buildRequest(Config $config): ?array
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            return null;
        }

        return [
            'url'     => $this->currentUrl(),
            'method'  => $_SERVER['REQUEST_METHOD'],
            'headers' => $this->scrubber->scrub($this->extractHeaders(), $config->scrubFields),
        ];
    }

    private function currentUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        return "{$scheme}://{$host}{$uri}";
    }

    private function extractHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!str_starts_with($key, 'HTTP_')) {
                continue;
            }
            // HTTP_X_FOO_BAR -> X-Foo-Bar
            $name           = ucwords(strtolower(str_replace('_', '-', substr($key, 5))), '-');
            $headers[$name] = $value;
        }

        return $headers;
    }
}
