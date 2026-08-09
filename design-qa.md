# Request section design QA

## Evidence

- Source truth: `/Users/benjamin/.codex/generated_images/019fe60b-c5c8-75d3-8b86-0e812e911d8f/exec-2582c32e-dd0c-4707-949c-f9aa705c6350.png`
- Desktop implementation: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe60b-c5c8-75d3-8b86-0e812e911d8f/request-section-implemented.png`
- Focused details: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe60b-c5c8-75d3-8b86-0e812e911d8f/request-details-implemented.png`
- Phone implementation: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe60b-c5c8-75d3-8b86-0e812e911d8f/request-details-iphone.png`
- Side-by-side comparison: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe60b-c5c8-75d3-8b86-0e812e911d8f/request-section-comparison.png`
- Refined desktop implementation: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe60b-c5c8-75d3-8b86-0e812e911d8f/request-details-refined.png`
- Refined details implementation: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe60b-c5c8-75d3-8b86-0e812e911d8f/request-details-refined-focused.png`
- Refined phone implementation: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe60b-c5c8-75d3-8b86-0e812e911d8f/request-details-refined-iphone.png`
- Refined side-by-side comparison: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe60b-c5c8-75d3-8b86-0e812e911d8f/request-details-refined-focused-comparison.png`
- Collapsed default implementation: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe60b-c5c8-75d3-8b86-0e812e911d8f/request-details-collapsed-default-focused.png`
- Expanded-after-click implementation: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe60b-c5c8-75d3-8b86-0e812e911d8f/request-details-collapsed-then-open-focused.png`
- Desktop viewport and pixels: 1440 × 900 at 1× density
- Phone viewport and pixels: 390 × 844 at 1× density
- State: light and dark themes, Request selected, request details closed by default and opened on demand, Headers selected

## Comparison

- The implementation keeps the target hierarchy: outcome strip, three-step trace, linked markers, response facts, a clear section gap, and an open details panel.
- Dot centers align with their step headings at a measured 0 px difference. Both connector lines meet the next dot at a measured 0 px gap.
- The package shell, type scale, colors, and spacing stay within the existing New Debug Bar system instead of copying mock-only shell styling.
- The details choices use real captured data. Query replaces the mock's separate Cookies choice because cookies are only available as a redacted header.
- Light and dark states retain readable contrast. The 390 px layout keeps all controls usable and wraps long values without page-level horizontal overflow.
- The refined connector measures 2 px, matching the 2 px dot border. Every desktop detail selector measures exactly 159 px against a 159 px available track; every phone selector measures 167 px in its grid track.
- Selector and panel count labels now contain digits only. The rendered example shows `8`, `0`, `0`, and `0` without repeated nouns or status phrases.
- Request details now render closed on first view. Opening the native disclosure still reveals the same full-width selectors and captured data.

## Findings and fixes

- P2, resolved: the first phone pass forced the details table to 30 rem wide, which clipped values and required sideways scrolling. The minimum width was removed so name and value columns wrap within the phone viewport.
- P2, resolved: the timeline connector was optically weaker than its markers. Its stroke now matches the dots at 2 px.
- P2, resolved: detail selector backgrounds hugged their content instead of filling their track. Every selector now uses the full available width on desktop and phone.
- P3, resolved: detail counts repeated labels such as `values`, `fields`, and `parameters`. Navigation and panel headers now show only the number.
- P2, resolved: Request details initially consumed a large part of the inspector. The disclosure now starts closed and remains available with one click.
- No P0 or P1 issues remained after the final comparison.

## Final result

passed

---

# Overview design QA

## Target and capture

- Reference: `/Users/benjamin/.codex/generated_images/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/exec-9d56c4e0-19e4-46b1-8f1a-2c03371d59ec.png`
- Implementation: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/overview-implementation-1547x1017.png`
- Side-by-side comparison: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/overview-reference-vs-implementation.png`
- Comparison viewport: 1547 × 1017 for both images.

## Result

Passed. The implementation keeps the selected hierarchy: a short ranked activity list followed by an expanded, tabbed runtime table. It removes the repeated request context, the “more sections” row, standalone runtime counts, and all activity-row icons. Activity rows have zero left and right padding.

The main intentional differences are:

- The inspector stays capped at 1024 px, matching the earlier LG-breakpoint decision. The generated reference is wider.
- Activity chevrons were removed after the reference was made, matching the later instruction to remove item icons.
- Values and section counts come from the captured request instead of the generated reference data.

## Checks

- Light and dark themes at 1440 × 900.
- Light and dark themes at 390 × 844.
- Runtime category selection by keyboard.
- No horizontal overflow at 390 px.
- No browser JavaScript errors.
- Updated visual baselines inspected, then rerun without update mode.

---

# Models design QA

## Target and capture

- Reference: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/models-audit/05-focused-model-expanded.png`
- Same-viewport implementation: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/models-qa/implementation-1672x941.png`
- Same-viewport comparison: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/models-qa/reference-vs-implementation-1672x941.png`
- Comparison viewport and pixels: 1672 × 941 at 1× density.
- Automated captures: light and dark at 1440 × 900, plus light and dark at 390 × 844.
- State: Models selected, repeated-load finding visible, StudioJob expanded, raw events closed.

## Comparison

- The implementation keeps the target hierarchy: a short reason for the section, one important finding, comparable model rows, record-level evidence, nested raw events, and a separate boot lifecycle disclosure.
- The inspector remains capped at the LG breakpoint, so it is intentionally narrower than the generated reference at the same viewport.
- The repeated-load definition is explicit. Null record identifiers are excluded instead of being reported as duplicates.
- Changed models rank above repeated retrievals because writes can affect application state. Repeated loads rank next, then total loads.
- The implementation uses counts and highlighted record rows instead of the mock's unlabeled progress bars. The exact number and the supporting records are clearer evidence for developers.
- Expand all opens the five model summaries while leaving every raw JSON disclosure closed.

## Findings and fixes

- P2, resolved: initial test data relied on Eloquent's process-wide boot state, so boot lifecycle evidence could disappear between visual cases. The fixture now emits a stable 10-event lifecycle across five classes.
- P2, resolved: numeric stabilization changed real model counts in narrow screenshots. Model metrics and raw-event counts are now preserved while timing values remain deterministic.
- P2, resolved: the record table could exceed 390 px. Its overflow is contained inside the expanded model, while the inspector and main panel remain within the viewport.
- P2, resolved: new analysis payloads exceeded the smallest MCP response budget. UI-only model groups and boot items are removed at the MCP boundary while raw section items remain paginated.
- No P0 or P1 issues remained after the final comparison.

## Final result

passed
