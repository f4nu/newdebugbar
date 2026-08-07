# New Debug Bar

New Debug Bar is a modern debug bar for Laravel.

It helps you answer five basic questions:

- What happened?
- What went wrong?
- Why did it happen?
- Where should I look?
- What should I inspect next?

The package adds a small toolbar to the bottom of your local Laravel app. Open it to inspect requests, queries, exceptions, logs, events, jobs, mail, cache activity, and more.

It works with Blade, Livewire, and Inertia applications.

## Requirements

- PHP 8.3 or newer
- Laravel 13
- Livewire 4.1 or newer

## Install

Install the package as a development dependency:

```bash
composer require --dev newdebugbar/new-debug-bar
```

Laravel discovers the package automatically. Open your app in the `local` environment and the toolbar will appear at the bottom of the page.

Keep the package in `require-dev` so it is not installed in production.

## Configure

The default settings work without configuration. To publish the optional config file, run:

```bash
php artisan vendor:publish --tag=new-debug-bar-config
```

To disable the bar without removing the package, add this to `.env`:

```dotenv
NEW_DEBUG_BAR_ENABLED=false
```

## Local and safe by default

New Debug Bar runs only in the `local` environment by default. Profiles are stored as short-lived JSON files in `storage/framework/new-debug-bar`; no database is required.

Sensitive values are redacted. The package does not call an AI service, replay requests, retry jobs, send mail, or change your application data.

## Development

```bash
composer install
npm install
npm run build
composer test
```

## License

Copyright © 2026 Benjamin Crozat.

New Debug Bar is open source software released under the MIT License. Copies or substantial portions of the package must keep the copyright and license notice.
