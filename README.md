# New Debug Bar (for Laravel)

New Debug Bar is a modern debugging tool for Laravel, built for developers and coding agents. It helps you understand each request and find problems without cluttering your local app.

A compact bar at the bottom of the page gives you quick access to database queries, errors, logs, events, jobs, mail, cache use, and more.

It works with Blade, Livewire, and Inertia.

## Use with coding agents

New Debug Bar saves a clear profile for each request. A coding agent can use it to understand what happened, spot errors or slow work, explain the likely cause, and point you to what to inspect next. Common secrets are hidden, and the agent can only read the saved profiles.

Instead of giving the agent large logs, browser dumps, or a broad codebase and asking it to guess, it can request the small, relevant, structured summary or finding from a saved profile. Across nine representative profiles, focused MCP reads used a median 68% fewer tokens than compact full-profile dumps, reducing irrelevant input and helping the agent investigate faster.

### Codex plugin

The optional Codex plugin is the simplest path for Codex users. It gives Codex a New Debug Bar skill and access to profiles from the Laravel app you have open, without manual MCP setup.

[Set up the Codex plugin](docs/mcp.md#codex-plugin).

### Other MCP tools

Other compatible AI tools can use the same focused, read-only profile data through optional Model Context Protocol (MCP) support. Follow the [MCP setup guide](docs/mcp.md) for Claude Code, Cursor, VS Code, and other local MCP clients.

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
