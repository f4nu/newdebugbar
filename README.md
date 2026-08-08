# New Debug Bar (for Laravel)

New Debug Bar is a modern debugging tool for Laravel, built for developers and coding agents. It helps you understand each request and find problems without cluttering your local app.

A compact bar at the bottom of the page gives you quick access to database queries, errors, logs, events, jobs, mail, cache use, and more.

It works with Blade, Livewire, and Inertia.

## Use with coding agents

### Codex plugin

The optional Codex plugin is the simplest way to use New Debug Bar with Codex. It starts the local MCP connection and adds a debugging skill, so Codex can inspect the request profiles saved by the Laravel app you have open.

[Set up the Codex plugin](docs/mcp.md#codex-plugin).

### Other MCP tools

New Debug Bar also includes an optional local Model Context Protocol (MCP) server for other AI tools that support MCP. It gives an assistant focused, structured, read-only access to saved and redacted request profiles. The assistant can investigate what happened and recommend where to look next without guessing from logs or asking you to copy debug data by hand.

The Codex plugin uses this MCP connection automatically. For non-Codex tools, MCP is optional. Follow the [MCP setup guide](docs/mcp.md) for Claude Code, Cursor, VS Code, and other local MCP clients.

## Requirements

- PHP 8.1 or newer
- Laravel 10 or newer

## Install

Until v1 is tagged, install the current development version:

```bash
composer require --dev newdebugbar/newdebugbar:dev-main
```

Laravel loads it for you. Visit your app in the `local` environment. The bar will appear at the bottom of the page.

New Debug Bar uses Livewire 4 for its own interface. Your app does not need to use Livewire. Apps that already use Livewire 3 are not supported.

Keep the package in `require-dev` so it is not installed in production.

## Settings

The package works without any setup. To change its settings, publish the config file:

```bash
php artisan vendor:publish --tag=newdebugbar-config
```

To turn the bar off, add this to `.env`:

```dotenv
NEWDEBUGBAR_ENABLED=false
```

## Your data

The package runs only in the `local` environment by default. It saves short-lived profile files in `storage/framework/newdebugbar`. It does not need a database.

Common secrets are hidden. The package does not call an AI service or change your app's data.

## Work on the package

```bash
composer install
npm install
npm run build
composer test
```

## License

New Debug Bar uses the Apache License 2.0.
