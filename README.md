# New Debug Bar

New Debug Bar is a modern, Laravel-only debug bar built with Livewire and Tailwind CSS.

It adds a thin bottom toolbar and a full-width bottom inspector to local Laravel pages. The package owns its compiled CSS, JavaScript, icons, syntax highlighting, Outfit font, light theme, and dark theme, so the interface stays the same in every host application.

## Support

| Package | Supported versions |
| --- | --- |
| PHP | 8.3, 8.4, 8.5 |
| Laravel | 13.x |
| Livewire | 4.1 or newer 4.x release |
| Node.js for package development | 24 or newer |

Laravel 12, Livewire 3, production profiling, and Laravel Octane are not supported in `0.1.x`. Octane support requires real concurrent-request tests before it can be claimed.

## What it captures

- Request, route, middleware, response size, session presence, and authentication presence
- Query totals, slow queries, repeated patterns, likely N+1 evidence, bindings, and call sites
- Livewire components, actions, changed field names, validation failures, and payload sizes
- Models, cache operations, views, events, logs, and exceptions
- Outbound HTTP results, queue activity, mail shape, notification outcomes, and direct Redis commands
- Artisan commands, `php artisan test` runs, and individual queue-worker jobs as non-HTTP profiles
- Retained history, same-path comparison, a relative timeline, and deterministic findings

Profiles stay local in `storage/framework/cache/new-debug-bar`. They are bounded, short-lived JSON files and do not use a database.

## Install from a local checkout

Add a development path repository to the Laravel application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../../new-debug-bar",
            "options": {
                "symlink": true
            }
        }
    ],
    "require-dev": {
        "newdebugbar/new-debug-bar": "dev-main"
    }
}
```

Then run:

```bash
composer update newdebugbar/new-debug-bar --with-dependencies
```

Laravel discovers the provider automatically. Keep the package in `require-dev`, use Composer's `--no-dev` option for production installs, and disable the classic Laravel Debugbar while testing New Debug Bar.

An application without Livewire receives Livewire through Composer. Livewire 3 creates a normal Composer version conflict. The host does not need to change its Vite or Tailwind setup.

## Configuration

Publish the small optional config file:

```bash
php artisan vendor:publish --tag=new-debug-bar-config
```

Set `NEW_DEBUG_BAR_ENABLED=false` to disable the package. By default it registers only when Laravel's environment is `local`.

String query bindings are masked because positional bindings do not have safe field names. Set `NEW_DEBUG_BAR_QUERY_BINDINGS=full` only when complete local values are knowingly required, or `none` to omit all bindings.

The config also controls the theme, slow thresholds, retained profile count and age, MCP limits, collector limits, and bounded call-site capture.

## Privacy and safety

New Debug Bar is read-only. It does not replay requests, run query plans, retry jobs, send messages, clear caches, or change application state.

It excludes uploaded files, cache values, full model attributes, mail bodies and subjects, recipient identities, notification data, Redis arguments, cookies, authorization headers, and common secret fields. Redis keys and cache keys are stored as short hashes. Its own Livewire updates are excluded from profiling.

The package never calls an AI model. It only captures facts and applies explicit rules.

## Local MCP server

The package uses Laravel's first-party MCP package and registers one local server named `new-debug-bar`:

```bash
php artisan mcp:start new-debug-bar
```

It exposes four bounded, read-only tools:

- `list-debug-profiles`
- `get-debug-profile-section`
- `inspect-debug-queries`
- `get-debug-findings`

Use the `X-New-Debug-Bar-Profile` response header as the exact correlation ID. MCP responses are versioned, redacted, paginated, and size-limited. No web MCP route is registered.

Smoke-test the server with Laravel MCP's Inspector command:

```bash
php artisan mcp:inspector new-debug-bar
```

## Test assertions

Optional assertions use the same analyzers as the inspector and MCP:

```php
use NewDebugBar\Testing\ProfileAssertions;

ProfileAssertions::stored($profileId)
    ->assertNoRepeatedQueries()
    ->assertNoLikelyNPlusOneQueries()
    ->assertQueryCountAtMost(20)
    ->assertQueryTimeAtMost(100)
    ->assertDurationAtMost(500)
    ->assertPeakMemoryAtMost(64)
    ->assertNoErrors();
```

Use `ProfileAssertions::for($profile)` when the profile array is already available.

## Package development

```bash
composer install
npm install
npm run build
composer test
```

Useful focused commands:

```bash
composer lint:check
composer test:php
npm test
composer test:browser
composer benchmark -- 2000
```

Refresh intentional visual changes with `UPDATE_VISUAL_BASELINES=1 composer test:browser`, review every changed PNG, and then rerun the normal browser command. The benchmark compares a disabled baseline with a warmed collector-core profile. It is synthetic, so run it inside the target application when host-specific numbers matter. Compiled `dist` assets and visual snapshots are committed so each installation receives and verifies the same interface.

## License

New Debug Bar is open source software released under the MIT License.
