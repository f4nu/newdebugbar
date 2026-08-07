# New Debug Bar

New Debug Bar helps you understand what your Laravel app did during a request.

It helps answer five questions:

- What happened?
- What went wrong?
- Why did it happen?
- Where should I look?
- What should I check next?

The package adds a small bar to the bottom of your app. Open it to see requests, database queries, errors, logs, events, jobs, mail, cache use, and more.

It works with Blade, Livewire, and Inertia.

## Requirements

- PHP 8.3 or newer
- Laravel 13
- Livewire 4.1 or newer

## Install

Add the package as a development tool:

```bash
composer require --dev newdebugbar/new-debug-bar
```

Laravel loads it for you. Visit your app in the `local` environment. The bar will appear at the bottom of the page.

Keep the package in `require-dev` so it is not installed in production.

## Settings

The package works without any setup. To change its settings, publish the config file:

```bash
php artisan vendor:publish --tag=new-debug-bar-config
```

To turn the bar off, add this to `.env`:

```dotenv
NEW_DEBUG_BAR_ENABLED=false
```

## Your data

The package runs only in the `local` environment by default. It saves short-lived profile files in `storage/framework/new-debug-bar`. It does not need a database.

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
