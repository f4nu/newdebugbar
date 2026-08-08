# New Debug Bar (for Laravel)

New Debug Bar is a modern debugging tool for Laravel, built for developers and coding agents. It helps you understand each request and find problems without cluttering your local app.

A compact bar at the bottom of the page gives you quick access to database queries, errors, logs, events, jobs, mail, cache use, and more.

It works with Blade, Livewire, and Inertia.

## Built for coding agents

New Debug Bar includes a local Model Context Protocol (MCP) server for tools such as Codex. It gives an agent the same request facts shown in the debug bar as clear, structured data.

An agent can list recent profiles, open any captured section, inspect database queries, and read findings. The `X-NewDebugBar-Profile` response header can link a browser request to its exact profile. Private values are hidden, results are kept small, and the tools only read saved profiles.

Your coding tool starts the MCP server when it needs it. Follow the [MCP setup guide](docs/mcp.md) for Codex, Claude Code, Cursor, VS Code, and other local MCP clients.

## Requirements

- PHP 8.1 or newer
- Laravel 10 or newer
- Livewire 4.1 or newer

## Install

Until v1 is tagged, install the current development version:

```bash
composer require --dev newdebugbar/newdebugbar:dev-main
```

Laravel loads it for you. Visit your app in the `local` environment. The bar will appear at the bottom of the page.

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
