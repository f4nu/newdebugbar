# New Debug Bar

## Scope and compatibility

- Build for Laravel only. Do not add support for other PHP frameworks.
- Match the minimum PHP and Laravel versions supported by Livewire 4.
- Use `NewDebugBar` or `newdebugbar` as one word in machine-facing names. Use “New Debug Bar” in text written for people.

## Product behavior

- Make the full local debugging experience work immediately. Do not hide useful diagnostics behind opt-in flags or masked defaults only because the captured data may be sensitive.
- Add a config value only when developers have a real, repeated reason to change the behavior. Every value must have a distinct runtime effect and a clear reason for its default. Otherwise, use one fixed product behavior and remove the setting, branches, and tests.
- Use a protective default only when the normal behavior could change external state, break the host app, or create unbounded work or storage. Local diagnostic visibility by itself is not a reason to disable a feature.
- Treat the local MCP server as a main product feature. Explain that coding agents can read exact debug data instead of guessing from a web page.

## Documentation

- Keep the public README short. Explain why the package exists and how to start using it.
- Keep client-specific MCP setup in `docs/mcp.md`. Link to it from the README.
- Keep test reports, support tables, and long setup notes out of the README.

## Interface priorities

- Keep the bar visually quiet until something needs attention. It should feel at home on the page while a developer works.
- Help developers answer: What happened? What is wrong? Why? Where? What should I check next?
- Show the request, errors, query count, and duration first.
- Keep framework internals, raw data, hashes, and repeated facts out of the main view.
- A finding should explain the problem, why it matters, where it came from, and what to do next.
- Do not show two findings for the same cause.
