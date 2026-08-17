# Mobile Toolbar Design QA — Pointer and Metric Refinement

## Comparison target

- Source visual truth:
  - Facts open: `/Users/benjamin/.codex/generated_images/01a00e9f-8958-7c80-bf58-058f100a25fc/exec-a6110b76-e668-4922-8072-3ede1d9eb9a2.png` (853 × 1844 pixels).
  - Actions open: `/Users/benjamin/.codex/generated_images/01a00e9f-8958-7c80-bf58-058f100a25fc/exec-c817087b-d8bd-42d3-b0b1-89b2d8eec7b4.png` (852 × 1854 pixels).
- Rendered implementation:
  - Closed: `/Users/benjamin/Sites/new-debug-bar/tests/VisualBaselines/toolbar-narrow-light.png` and `/Users/benjamin/Sites/new-debug-bar/tests/VisualBaselines/toolbar-narrow-dark.png`.
  - Facts open: `/Users/benjamin/Sites/new-debug-bar/tests/VisualBaselines/toolbar-narrow-facts-light.png` and `/Users/benjamin/Sites/new-debug-bar/tests/VisualBaselines/toolbar-narrow-facts-dark.png`.
  - Actions open: `/Users/benjamin/Sites/new-debug-bar/tests/VisualBaselines/toolbar-narrow-actions-light.png` and `/Users/benjamin/Sites/new-debug-bar/tests/VisualBaselines/toolbar-narrow-actions-dark.png`.
- Viewport and density: both generated sources were normalized to 390 × 844 pixels. Implementation captures are 390 × 844 CSS pixels at 1× screenshot density.
- States: toolbar closed, request-facts menu open, and action menu open, in light and dark themes.

## Evidence

- Full-view facts comparison: `/Users/benjamin/.codex/visualizations/2026/08/17/01a00e9f-8958-7c80-bf58-058f100a25fc/mobile-toolbar-facts-comparison-v2.png`.
- Full-view actions comparison: `/Users/benjamin/.codex/visualizations/2026/08/17/01a00e9f-8958-7c80-bf58-058f100a25fc/mobile-toolbar-actions-comparison-v2.png`.
- Focused facts comparison: `/Users/benjamin/.codex/visualizations/2026/08/17/01a00e9f-8958-7c80-bf58-058f100a25fc/mobile-toolbar-facts-focused-comparison-v2.png`.
- Focused actions comparison: `/Users/benjamin/.codex/visualizations/2026/08/17/01a00e9f-8958-7c80-bf58-058f100a25fc/mobile-toolbar-actions-focused-comparison-v2.png`.
- Isolated in-app browser check: `http://example-blade-app.test/login` at 390 × 844. Both menus opened, both pointers aligned to their triggers, real values `7.05 ms` and `10 MB` fit without clipping, and the console had no errors or warnings.

The source and implementation use different host-page fixtures, so the component region is the fidelity target. Focused comparison was required because the toolbar labels and icons are too small to judge reliably in the full view.

## Findings

- No actionable P0, P1, or P2 differences remain.
- Fonts and typography: the implementation keeps New Debug Bar's Outfit type system. Three 11-pixel tabular values sit above quieter 9-pixel labels; real request values remain fully visible at 390 pixels.
- Spacing and layout rhythm: the request control gives 16 pixels back to the summary, which now uses the available width for Queries, Time, and Peak. Menu rows remain slightly taller and wider than the generated source to preserve 44-pixel touch targets; this is an accepted P3 difference.
- Colors and visual tokens: the neutral glass surfaces, indigo summary state, dividers, borders, and shadows retain light/dark parity with the existing product tokens.
- Image and icon fidelity: no raster assets are needed. The toolbar keeps one centered activity icon and the established outlined icon set. Both popovers now use the same 14-pixel pointer treatment and align that pointer with the opening control.
- Copy and content: the compact bar now exposes query count, total request time, and peak memory before opening the facts menu. All four facts and all three actions remain available. The visible “Request facts” and “Debug bar” headings remain intentionally removed.
- Accessibility and interaction: both triggers expose expanded state, menus close with Escape or an outside click, focus returns to the trigger, and opening the palette or inspector hands focus to the new surface.

## Comparison history

1. P2, fixed: the previous implementation omitted the pointer under both popovers. The repeated surface, transition, alignment, and placement logic now lives in one shared component with a pointer for both menu variants.
2. P2, fixed: the first three-metric pass clipped a real `7.74 ms` value in the isolated browser preview. The request control was narrowed, metric padding and type were tightened, and a browser assertion now rejects clipped metric values.
3. The revised closed and open captures were inspected in both themes, then the same visual group passed again without baseline-update mode. No P0, P1, or P2 findings remain.

## Implementation checklist

- [x] Keep desktop toolbar controls unchanged.
- [x] Show request, three useful metrics, and ellipsis on mobile.
- [x] Remove visible popover headings.
- [x] Reuse one arrowed popover shell for both menus.
- [x] Keep facts and action menus mutually exclusive.
- [x] Verify 44-pixel targets, unclipped values, focus return, Escape, palette, and inspector actions.
- [x] Capture and pass closed, facts-open, and actions-open baselines in both themes.

final result: passed

---

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

---

# Queries design QA

## Target and capture

- Reference: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/queries-audit/10-mockup-queries-flat-details.png`
- Earlier implementation: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/queries-audit/implementation-queries-1350x1168.png`
- Revised implementation: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/queries-audit/implementation-queries-tabs-1350x1168.png`
- Original reference comparison: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/queries-audit/reference-vs-implementation.png`
- Full before-and-after tab comparison: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/queries-audit/queries-tabs-before-after.png`
- Focused before-and-after tab comparison: `/Users/benjamin/.codex/visualizations/2026/08/09/019fe6a7-ddd7-7982-ae2f-323a1f4800d3/queries-audit/queries-tabs-focused-before-after.png`
- Reference pixels: 1349 × 1166. Earlier and revised implementation viewports and pixels: 1350 × 1168 at 1× density. The original comparison trims the implementation by 1 px horizontally and 2 px vertically. The tab comparisons use equal 1350 × 1168 captures; the focused comparison uses equal 1024 × 360 crops.
- Automated captures: light and dark at 1440 × 900, plus light and dark at 390 × 844.
- State: Queries selected, All filter active, first repeated execution expanded, Application stack selected.

## Comparison

- The implementation keeps the selected hierarchy: three request-level metrics, query filters, search and sort controls, a single repeated-pattern summary, and flat execution evidence.
- Repeated SQL is shown once for the group. Individual executions reveal bindings and application frames without adding another framed card.
- The N+1 finding uses amber text instead of the mock's blue treatment. Blue now consistently means an action or selected control.
- Query actions are consolidated into one menu. The repeated copy buttons and Open in Editor link were removed.
- Bindings and Application stack now share one contained tab rail. The selected view has a solid surface, border, blue text, and subtle elevation; the inactive view remains visibly clickable. Query actions sit outside the control.
- Standalone reads and writes remain in the same result list, so the section does not hide useful work that falls outside a repeated pattern.
- The inspector remains capped at the LG breakpoint, matching the existing readability limit.

## Fidelity surfaces

- Typography: the tabs retain the package typeface, weight, and compact 10 px control scale, but use 32 px minimum targets and stronger selected text.
- Spacing and layout: the rail adds consistent 4 px internal spacing and 12 px horizontal tab padding without widening the query card or overflowing at 390 px.
- Colors and tokens: all surfaces use the existing zinc and indigo tokens in light and dark themes. The selected state no longer depends on a thin underline that can disappear against a divider.
- Image quality: this section contains no raster imagery. Existing package icons remain sharp and unchanged.
- Copy: Bindings, Application stack, their counts, and Query actions remain concise and accurate.

## Findings and fixes

- P2, resolved: repeated executions appeared both as standalone rows and inside their group. Grouped executions now have one visible source of truth.
- P2, resolved: bindings, stack frames, and copy controls competed side by side. Evidence is now split into keyboard-accessible tabs, with utility actions in one menu.
- P2, resolved in the second pass: the initial tab styling still looked like static metadata because its selected underline disappeared into the panel divider. The focused before-and-after comparison shows the replacement contained rail, distinct selected surface, and separated Query actions. Arrow keys, click switching, ARIA selection, and both panels remain functional.
- P2, resolved: sorting only rearranged standalone queries. Repeated groups and their executions now follow the selected execution or duration order too.
- P2, resolved: an Overview slow-query link could reset the panel to the top after revealing the evidence. It now scrolls to either a slow standalone query or a slow repeated group.
- P2, resolved: result counts described cards instead of query executions. Counts now include every visible execution, including those represented by a repeated group.
- P2, resolved: the first narrow layout could overflow around the filter and search controls. The 390 px light and dark captures now keep all content inside the inspector.
- P3, resolved: long connection names, SQL previews, file paths, and functions could crowd timing evidence. They truncate visually and expose the full value through the native title text.
- No P0 or P1 issues remained after the final combined comparison.

## Final result

passed
