---
name: use-new-debug-bar
description: Install, set up, and use New Debug Bar in a Laravel app. Use when Codex needs to add the package, connect its local MCP server, inspect a browser request or saved profile, investigate findings or queries, or explain what happened, what is wrong, why, where, and what to inspect next.
---

# Use New Debug Bar

Use the package's local MCP tools to read exact, saved Laravel request data. Keep the answer short and lead with the useful result.

## Set up the package

1. Confirm the current folder is a Laravel app by finding `artisan` and `composer.json`.
2. Run `composer show newdebugbar/newdebugbar` before changing anything.
3. If the package is missing, explain that it is a development-only dependency and ask before running `composer require --dev newdebugbar/newdebugbar`.
4. Do not publish the config file by default. The package works without it. Run `php artisan vendor:publish --tag=newdebugbar-config` only when the user asks to change a setting.
5. Confirm the app uses the `local` environment and New Debug Bar is enabled.
6. If the MCP server tried to start before the package was installed, restart the Codex task after installation.

## Inspect a request

1. Make the request in the browser when the user wants live proof.
2. Use the `X-NewDebugBar-Profile` response header when it is available.
3. Otherwise, list recent profiles and match the method, path, status, request kind, and time. Do not trust the newest item when background requests may have run.
4. Read findings and the smallest useful sections. Inspect query details only when the request points to a database problem.
5. Separate confirmed facts from guesses.

## Explain the result

Answer these questions in order:

1. What happened?
2. What is wrong?
3. Why did it happen?
4. Where should the developer look?
5. What should they inspect or try next?

Prefer a useful summary over a dump of raw profile data. Mention the exact profile, request, and status so the developer knows which request the answer describes. The MCP tools only read saved, redacted profiles; do not claim they changed the app.
