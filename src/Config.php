<?php

namespace BugSquid;

final class Config
{
    private const DEFAULT_SCRUB_FIELDS = [
        'authorization',
        'cookie',
        'set-cookie',
        'x-api-key',
        'api_key',
        'password',
        'secret',
        'token',
        'php_auth_pw',
    ];

    public readonly string $endpoint;
    public readonly string $ingestKey;
    public readonly string $environment;
    public readonly ?string $release;
    public readonly ?string $serverName;
    /** @var string[] */
    public readonly array $scrubFields;

    private function __construct(
        string $endpoint,
        string $ingestKey,
        string $environment,
        ?string $release,
        ?string $serverName,
        array $scrubFields,
    ) {
        $this->endpoint    = $endpoint;
        $this->ingestKey   = $ingestKey;
        $this->environment = $environment;
        $this->release     = $release;
        $this->serverName  = $serverName;
        $this->scrubFields = $scrubFields;
    }

    public static function fromArray(array $config): self
    {
        return new self(
            endpoint:    $config['endpoint'],
            ingestKey:   $config['ingest_key'],
            environment: $config['environment'] ?? 'production',
            release:     $config['release'] ?? null,
            serverName:  $config['server_name'] ?? (gethostname() ?: null),
            scrubFields: $config['scrub_fields'] ?? self::DEFAULT_SCRUB_FIELDS,
        );
    }
}
