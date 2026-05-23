<?php

namespace BugSquid;

/**
 * Static facade for plain-PHP (non-Laravel) projects.
 */
final class BugSquid
{
    private static ?Client $client = null;

    public static function init(array $config): void
    {
        try {
            self::$client = new Client(Config::fromArray($config));
            self::$client->register();

            set_exception_handler(static function (\Throwable $e): void {
                self::captureException($e);
            });

            // Catch fatal errors (E_ERROR, E_PARSE, etc.) that bypass set_exception_handler.
            register_shutdown_function(static function (): void {
                $error = error_get_last();

                if ($error === null) {
                    return;
                }

                $fatals = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

                if (in_array($error['type'], $fatals, true)) {
                    self::captureException(new \ErrorException(
                        $error['message'],
                        0,
                        $error['type'],
                        $error['file'],
                        $error['line'],
                    ));
                    self::$client?->flush();
                }
            });
        } catch (\Throwable) {
            // SDK init must never crash the host app.
        }
    }

    public static function captureException(\Throwable $e, array $extra = []): void
    {
        try {
            self::$client?->captureException($e, $extra);
        } catch (\Throwable) {}
    }
}
