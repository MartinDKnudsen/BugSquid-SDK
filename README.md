# BugSquid PHP SDK

Captures exceptions and POSTs them to a [BugSquid](https://bugsquid.io) ingest endpoint.
Zero runtime dependencies beyond PHP 8.2 — raw cURL, no Guzzle.

---

## Laravel (11 / 12)

The package auto-registers via Composer's package discovery.

**1. Install**

```bash
composer require bugsquid/sdk
```

**2. Publish the config**

```bash
php artisan vendor:publish --tag=bugsquid-config
```

**3. Add to `.env`**

```dotenv
BUGSQUID_ENDPOINT=https://your-bugsquid-instance.com/ingest
BUGSQUID_KEY=your-project-ingest-key
```

That's it. Unhandled exceptions are captured automatically via Laravel's logging pipeline
(the SDK listens to `MessageLogged` events that carry an `exception` in their context,
which is exactly what Laravel's default exception handler emits).

### Optional: explicit capture in `bootstrap/app.php`

If you prefer to control capture yourself rather than relying on the log event, add a
`report` callback inside `withExceptions()`:

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->report(function (\Throwable $e) {
        app(\BugSquid\Client::class)->captureException($e);
    });
})
```

---

## Plain PHP (no framework)

```php
require 'vendor/autoload.php';

\BugSquid\BugSquid::init([
    'endpoint'    => 'https://your-bugsquid-instance.com/ingest',
    'ingest_key'  => 'your-project-ingest-key',
    'environment' => 'production',   // optional, default 'production'
    'release'     => '1.2.3',        // optional
    'server_name' => 'web-1',        // optional, default gethostname()
]);

// Uncaught exceptions and fatal errors are now captured automatically.
// You can also capture manually:
try {
    riskyOperation();
} catch (\Throwable $e) {
    \BugSquid\BugSquid::captureException($e, [
        'user'  => ['id' => 42, 'email' => 'user@example.com'],
        'extra' => ['order_id' => 99],
    ]);
}
```

`BugSquid::init()` installs:
- A `set_exception_handler` that captures uncaught exceptions.
- A `register_shutdown_function` that catches fatal errors (`E_ERROR`, `E_PARSE`, etc.).
- A flush on shutdown (via `Client::register()`) so buffered events are delivered before the
  process exits. On FPM hosts `fastcgi_finish_request()` is called first so the user's HTTP
  response is not delayed.

---

## Configuration reference

| Key | Env var | Default |
|-----|---------|---------|
| `endpoint` | `BUGSQUID_ENDPOINT` | — (required) |
| `key` | `BUGSQUID_KEY` | — (required) |
| `environment` | `BUGSQUID_ENVIRONMENT` | `APP_ENV` → `'production'` |
| `release` | `BUGSQUID_RELEASE` | `null` |
| `server_name` | `BUGSQUID_SERVER_NAME` | `gethostname()` |

Sensitive header/context keys are redacted to `[Filtered]` automatically.
Default scrub list: `authorization`, `cookie`, `set-cookie`, `x-api-key`, `api_key`,
`password`, `secret`, `token`, `php_auth_pw`. Override with `scrub_fields` in your config.
