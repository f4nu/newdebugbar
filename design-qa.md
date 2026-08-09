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
