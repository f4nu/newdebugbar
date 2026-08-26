# New Debug Bar design language

## Product character

New Debug Bar is a quiet diagnostic tool inside someone else's application. It should feel precise and at home on the page until something needs attention. The interface is not a dashboard of decorations. It is a work surface for understanding one request.

The primary reading order is:

1. Request or item identity.
2. Outcome and the few facts needed to judge it.
3. Evidence that explains why or where.
4. A next inspection only when the condition calls for one.

## Truth before presentation

- Show only facts present in the retained profile.
- Distinguish “not captured” from “empty,” “failed,” and “not applicable.”
- Do not infer a database write, model hydration source, notification delivery, HTTP cause, or application source from adjacent data.
- Keep evidence bounded. Prefer an application-only stack, retained payload, or limited group over an unbounded dump.
- Do not show multiple findings or rows for the same logical cause or operation.

## What to remove

Delete UI when it is any of the following:

- A badge, count, label, or metadata row already visible nearby.
- A heading that merely repeats a tab or table title.
- A prose explanation of an obvious field such as Source.
- Framework internals that do not help the developer choose a next step.
- Raw JSON that duplicates a clearer structured view.
- An ornamental icon, container, or hover background that competes with a simple clickable label.
- A button for an action the whole row or card can safely perform.

Do not delete unique evidence merely to make a view sparse. Move it to the appropriate detail tab or evidence block.

## Layout grammar

### Multiple items

Use an edge-to-edge list/detail workspace. The list owns its controls and list scrolling. The detail pane owns detail scrolling. On mobile, show either the list or the selected detail, never a squeezed desktop split.

### One item

Use a full-width focused reading view. Do not keep an empty list column for structural consistency.

### Empty selection

Do not select the first item merely to fill space. When selection is not a deliberate default, center a short instruction on both axes in the detail pane.

### Height and scrolling

- Make the complete parent height chain support the intended section height.
- Give each view one clear vertical scroll owner.
- Avoid nested full-height scroll regions.
- Verify that headers and controls stay reachable at short desktop heights.

### Alignment

- Keep list rows on stable tracks across different content lengths.
- Vertically center badges, outcomes, and numbers against the primary label.
- Use tabular numbers for values developers compare.
- Measure important column and control alignment in the browser; target a difference of no more than one pixel.
- Avoid gaps and borders that create unused gutters. Edge-to-edge workspaces use a top divider when a full card frame adds no value.

## Typography

- Use the interface typeface for paths, source locations, drivers, connections, table names, keys, labels, and prose.
- Use monospaced type only for actual code and numeric values inside tables.
- A class or callable such as `App\Models\User->notify` is code; a file location such as `app/Actions/Trips/RefreshTripWorkspace.php:150` is a source label.
- Syntax-highlight every real code snippet, including SQL, PHP callables, JSON, headers, and retained code-like payloads.
- Keep body and control text comfortably readable. Avoid 10px text for meaningful content.
- Use a restrained headline and close subheadline. Do not create a large gap between them.
- Do not use bullet-dot characters as inline separators.

## Color and emphasis

- Neutral is the default.
- Use red, amber, or another semantic color only when the state needs attention.
- Do not use blue monospaced text as a generic source treatment.
- Make links obvious with a simple underline when a filled hover treatment would add noise.
- Preserve hidden accessible meaning when a visible label is redundant.
- Use one prominent selected state. Avoid stacking a selected row, badge, card tint, and callout for the same fact.

## Controls

- Put the search field first, with its search icon on the left and balanced inset spacing.
- Put a compact filter dropdown on the right of search when the list needs one dimension of filtering.
- Remove sorting controls that do not help answer a real debugging question.
- Use a segmented control for a small, mutually exclusive set of detail views.
- Center detail tabs in their container. Keep them left-aligned only when another control in the same row makes centered placement misleading, as in Mail.
- Prefer explicit labels and stable widths. At a 390px viewport, an icon-only tab is acceptable only when the full set cannot fit, every icon has an accurate accessible label, and the meaning is familiar in context.
- “All” is often useful, but it is not a universal default. Choose the state that suppresses noise and answers the common question for that section.

## Headers and facts

- A selected-item header contains identity, not a pile of diagnostic facts.
- Pair operation or HTTP method badges with the key or URL on the same line.
- Use the shared equal-width operation badge in both list and detail headers.
- Put status in response evidence, host in request evidence, and source in source evidence.
- Keep fact groups compact, aligned, and free of duplicate header content.
- Use a table when several records share the same fields. Put totals in a clearly labeled `Count` row or column when they help comparison.

## Explanations

Use `inspector-explanation` only when developers need help interpreting or acting on a table or evidence group.

Its title must state the concrete question answered. Its description should:

- Explain only ambiguous or domain-specific meaning.
- Tell the developer what condition deserves attention.
- Offer the next check only under that condition.
- Stay neutral when the condition may be expected.

Do not explain Source, repeat a heading, or narrate ordinary table columns. A new developer deserves useful context, not more text.

## Actions

- Put copy, preview, open, or explain actions beside the exact evidence they affect.
- Make a whole Mail list item clickable instead of adding a redundant “View email” button.
- Provide visible success or failure feedback after clipboard and async actions.
- Remove actions that do not change what the developer can learn or do.

## Section-specific defaults

- Requests: preserve the lifecycle trace and collapsed Request details disclosure. It is an intentional exception to the shared inspector-workspace presentation; keep the newer global section shell, height chain, and host isolation around it without replacing its internal flow with tabs or list/detail controls.
- HTTP Client: list filter uses a dropdown; detail begins with Response, then Request, then Source.
- Cache: list operation filter uses a dropdown; operation badge and key share one header line; Raw precedes Source when raw data is useful.
- Mail: the entire list item opens the message; detail tabs may remain left-aligned when sharing a control row.
- Notifications: show actual channel outcomes; do not add a redundant “needs attention” badge to the detail panel.
- Models: no default selection; Records is the default selected-model tab; model list keeps a table header and a search field; drivers use the interface typeface; records, writes, and sources are separate useful views; do not duplicate table data in the header.
- Events: an Application default can be better than All when framework events dominate.

### Notification outcome language

- `partial` means that some captured channel attempts failed while others did not. State the concrete split, such as “1 of 2 channels failed,” instead of the vague “Needs attention” in detail evidence.
- A `sent` channel result means the application handed the notification to that channel successfully. It does not prove that a person ultimately received, opened, or acted on it.
- Keep the overall list status short. Put channel-specific outcomes and failure messages in Delivery.
- Successful channel rows stay neutral. Emphasize only the failed outcome and its captured failure evidence.

## Performance

- Do not render every retained payload, stack, editor, or detail panel on initial load.
- Render the active group, selected item, and active tab.
- Keep the Studio bounded to exactly one reusable component per iframe page.
- Treat large DOM output as a product defect even when it eventually renders.

## Studio catalog

- Group component navigation by practical purpose. Keep every component in exactly one compact navigation group.
- Give every reusable component one canonical Studio URL. A page may show useful states or variants of that component, but it must not mix in other component demos.
- Let the navigation identify the selected component. Do not repeat its name, purpose, type, breadcrumb, Blade tag, or source in a separate content header.
- Use a normal page scroll. Do not make the component navigation an independent overflow region.
- Keep the library and preview edge to edge. Do not nest the preview inside framed gallery cards or add a separate Studio header.
- Center the selected component vertically and horizontally in its preview container, including larger compositions.
- Use a searchable grouped navigation on desktop and a compact component picker on smaller outer viewports.
- Keep theme, viewport, and manually resized width state when moving between component pages.

## Host isolation

- Use one `#newdebugbar` root per document.
- Prefix semantic classes with `ndb-` and Tailwind utilities with `ndb:`.
- Use `data-ndb-*` for state and behavior hooks.
- Use `newdebugbar*` for IDs, events, and storage keys.
- Scope authored CSS beneath `#newdebugbar` or an `ndb-` class.
- Drive themes only from the package root's `data-ndb-theme`.
