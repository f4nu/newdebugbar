# New Debug Bar

New Debug Bar is a modern, Laravel-only debug bar built with Livewire 4 and Tailwind CSS 4.

It profiles local Laravel requests and presents the results as a bottom bar and responsive inspector. The inspector includes request, query, model, cache, view, event, log, and exception data.

## Requirements

- PHP 8.3 or newer
- Laravel 13
- Livewire 4.1 or newer

The package ships its own compiled, scoped CSS, JavaScript, and Outfit font. A host application does not need to add New Debug Bar to its Vite or Tailwind build.

## Install from a local checkout

Add a development path repository to the host application's `composer.json`:

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

Then run `composer update newdebugbar/new-debug-bar --with-dependencies`.

Laravel discovers the service provider automatically. New Debug Bar only registers collectors, middleware, UI, and asset routes in an allowed environment. The default allowed environment is `local`.

Optional configuration can be published with:

```bash
php artisan vendor:publish --tag=new-debug-bar-config
```

Set `NEW_DEBUG_BAR_ENABLED=false` to disable the package without removing it.

String query bindings are masked by default because positional bindings do not carry safe field names. Set `NEW_DEBUG_BAR_QUERY_BINDINGS=full` only when you knowingly need complete local binding values, or `none` to omit all bindings.

## Local MCP server

The package registers a local-only MCP server named `new-debug-bar`. Configure an MCP client to run this command from the Laravel application:

```bash
php artisan mcp:start new-debug-bar
```

The server exposes four read-only tools:

- `list-debug-profiles`
- `get-debug-profile-section`
- `inspect-debug-queries`
- `get-debug-findings`

Use the `X-New-Debug-Bar-Profile` response header as the exact profile ID for the request you want to inspect. Responses are versioned, redacted, paginated, and size-limited. The package does not register a web MCP endpoint and does not call an AI model.

## Local development

```bash
composer install
npm install
npm run build
composer test
```

Run `npm test` for the browser-state tests. Commit the generated `dist` assets after changing the interface so every host receives the same UI.

The package must stay in Composer's `require-dev` section. Production installs should use Composer's `--no-dev` flag.
