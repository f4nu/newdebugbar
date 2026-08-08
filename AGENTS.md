# New Debug Bar rules

## Commits

- Make small commits as you work.
- Keep each commit about one clear change. Commit its tests and built files with it.

## Product choices

- Build for Laravel only. Do not add support for other PHP frameworks.
- Match the minimum PHP and Laravel versions supported by Livewire 4.
- Use `NewDebugBar` or `newdebugbar` as one word in machine-facing names. Use “New Debug Bar” in text written for people.
- Treat the local MCP server as a main product feature. Explain that coding agents can read exact debug data instead of guessing from a web page.
- Keep the public README short. Explain why the package exists and how to start using it.
- Keep client-specific MCP setup in `docs/mcp.md`. Link to it from the README.
- Keep test reports, support tables, and long setup notes out of the README.
- Treat the first public release as v1. Do not add a changelog for work done before v1.
- Ask the user before changing the license or copyright owner.

## Interface

- Make the bar look clean and modern. It should feel at home on the page while a developer works.
- Do not use `·`, `•`, or `|` to split facts. Use space, labels, icons, or groups.
- Help developers answer: What happened? What is wrong? Why? Where? What should I check next?
- Show the request, errors, query count, and time first.
- Keep framework details, raw data, hashes, and repeated facts out of the main view.
- A finding should explain the problem, why it matters, where it came from, and what to do next.
- Do not show two findings for the same cause.

## Checking interface work

- Start with the built-in browser.
- Use these public, real applications as the canonical verification and benchmark targets:
  - [Northline Proofs](https://github.com/newdebugbar/northline-proofs) for conventional Laravel Blade.
  - [CommonGround Markets](https://github.com/newdebugbar/commonground-markets) for Laravel Livewire.
  - [Meridian Launch](https://github.com/newdebugbar/meridian-launch) for Laravel Inertia and Vue.
- Check all three when verifying the package across Blade, Livewire, and Inertia or running comparable benchmarks. Do not change an example app just to make a package test pass.
- Run one workbench server or browser test group at a time.
- Check light and dark themes, keyboard use, browser errors, and a 390px-wide screen.
- Update screenshot baselines only for planned changes. Look at each changed image, then run the same checks again.
