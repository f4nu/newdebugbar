# New Debug Bar guidance

## Git workflow

- Commit implementation work in small, logical steps as it progresses.
- Keep each commit focused on one clear change and include its related tests or generated artifacts.

## Product boundary

- Keep the package Laravel-only. Use package-owned compiled assets and normal DOM so host apps do not need Vite or Tailwind changes.
- Keep profiling local and read-only. Profiles are bounded JSON files, not database records.
- Never inject the interface into JSON, redirects, streams, downloads, Artisan commands, tests, or worker responses.

## Profile identity

- Treat the full page request as the current profile. Background JSON, AJAX, Livewire, and partial or deferred Inertia requests belong in History and must not replace it.
- A full Inertia visit may become current. Keep its real request type, including partial visits and Inertia redirects.
- Opening a retained History profile must not overwrite current-request identity. Always provide a clear way back to the current request.
- Reset details, filters, comparisons, and pending callbacks together when the selected profile changes. Ignore late responses from an older profile.

## Interface

- Never separate adjacent interface facts with standalone characters such as `·`, `•`, or `|`. Use spacing, grouping, labels, icons, or layout instead.
- Help developers answer: what happened, what is wrong, why, where, and what should I inspect next.
- Lead with request identity, failures, query count, and duration. Keep framework noise, raw payloads, hashes, middleware lists, CLI profiles, and repeated metadata quiet or on demand.
- Each finding should state the problem, why it matters, the best available application location, the next step, and a direct action when possible.
- Do not emit two findings for the same likely cause. Keep low-confidence query and framework activity out of the primary warning path.
- Fault pages must show bounded useful diagnostics without depending on a later Livewire request.
- Preserve focus trapping, focus restoration, Escape behavior, body scroll lock, and host-page isolation across Livewire morphs.
- Keep the main debugging facts and controls usable at a 390px viewport in both themes.

## Verification

- Use `../new-debug-bar-examples` for realistic Blade, Livewire, and Inertia checks. Do not change those apps merely to make package tests pass.
- Run PHP and JavaScript tests, rebuild `dist`, and use focused browser tests for the changed workflows.
- Keep resource use low: run one workbench server or browser suite at a time. The full browser matrix can exceed Composer's process timeout, so run it directly or in named groups when needed.
- Refresh visual baselines only for intentional UI changes, inspect the changed images, then rerun the same cases without update mode.
- Recheck profile switching, background discovery, History, focus, console output, light and dark themes, and 390px layouts after UI state changes.
