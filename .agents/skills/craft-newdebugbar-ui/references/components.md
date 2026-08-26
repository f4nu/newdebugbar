# Component system

The living visual catalog is `/__newdebugbar/studio`. Its source registry is `src/Presentation/StudioCatalog.php`.

Studio is an explicit allowlist of canonical reusable component families. It is not a directory browser. A Blade file does not become public merely because it lives in `resources/views/components`, and private section modules must not be added to Studio to satisfy a completeness count.

Each catalog family has one canonical page at `/__newdebugbar/studio/{component}` and one preview at `/__newdebugbar/studio/{component}/preview`. The iframe renders exactly one centered demo. Do not add a Studio header, explainer, breadcrumb, source panel, or framed card around the preview.

## Ownership boundary

Use these ownership levels:

1. **Shared primitives** own one visual or interaction rule, such as a field, badge, source link, or code block.
2. **Shared inspector patterns** own recurring composition and behavior across independent sections, such as a detail header, fact grid, or list-detail workspace.
3. **Private section modules** own section-specific labels, filters, rows, tabs, data normalization, and evidence. They belong beside their owning section and do not appear in Studio.

A shared component may depend only on another shared component. A private module may compose shared components. One section's private module must not become another section's dependency; extract the shared visual rule instead.

During the current migration, a private file may still live in `resources/views/components`. Register it under exactly one owner in `StudioCatalog::privateComponents()` until it is moved beside that section. Do not add it to Studio unless it independently satisfies the shared-component test below.

## When a component is shared

Make a component public only when at least one of these is true:

- Two independent product owners reuse the same visual or interaction rule.
- It is a foundational control or layout pattern that the product deliberately standardizes.

It must also satisfy all of these:

- Its API describes product semantics rather than one section's incidental markup.
- It does not expose section-specific state names.
- It has a useful, bounded Studio demo.
- It is the single canonical treatment for its role.

Similar section layouts do not justify a large component with many conditional props. Share stable geometry through slots and keep domain-specific content private.

## Shared primitives

| Component | Use |
| --- | --- |
| `code-block` | Syntax-highlighted code or retained code-like data. Pass the real language; never use it for an ordinary path or label. |
| `empty-state` | Calm no-results or section-empty message. `success` is only for a genuinely positive empty state. |
| `filter-tab` | One option inside `filter-tabs`. Express selection with `aria-selected` or `aria-pressed`; do not use it alone. |
| `icon` | Package-owned SVG at an explicit supported size. Prefer text when an icon would be ambiguous. |
| `icon-button` | Accessible icon-only action. Always provide an accessible name. |
| `inspector-action` | Compact labeled action beside the evidence it affects. |
| `inspector-operation-badge` | Neutral equal-width HTTP method or cache-operation badge. Use `wide` for longer operations and `outlined` in detail headers. |
| `inspector-source-link` | Underlined application-source action with no ornamental icon, padding, or hover fill. Pass `copy` when activation should copy the displayed location; keep that interaction inside the shared component. |
| `search-field` | Shared labeled search input with the icon fixed on the left and balanced inset spacing. Do not add a right-icon variant. |
| `select-field` | Native select with stable field geometry. Use for one list-filter dimension rather than a segmented strip. |

## Shared inspector patterns

| Component | Use |
| --- | --- |
| `filter-tabs` | Accessible tabs or segmented-control group. Give it a concrete label and place only `filter-tab` children inside it. |
| `inspector-definition-list` | Stack `inspector-definition-row` children with one divider system. |
| `inspector-definition-row` | One label/value pair. Use a danger tone only for an actual failed or harmful state. |
| `inspector-detail-back` | Mobile drill-in Back action. Use `persistent` only when the desktop flow truly needs it. |
| `inspector-detail-empty` | Center a short selection instruction in an unselected detail pane. |
| `inspector-detail-header` | Stable selected-item identity and optional actions. Use `grid` for fixed action placement and `wrap` for long identities. |
| `inspector-detail-pane` | Detail scroll owner with mobile drill-in behavior. Supply real open state, references, labels, and close behavior. |
| `inspector-detail-tabs` | Detail segmented tabs. Center by default; align left only when adjacent controls make centering misleading. |
| `inspector-evidence` | Optional label and compact aside plus syntax-highlighted evidence. Choose the actual language. |
| `inspector-explanation` | Friendly help for ambiguous evidence and a conditional next check. Do not explain obvious labels. |
| `inspector-fact` | One compact labeled fact inside `inspector-facts`. |
| `inspector-facts` | Responsive fact tracks. Use two to four columns and omit its border when the parent already supplies the divider. |
| `inspector-list-controls` | Optional list summary plus search and one or two trailing filters. Use the secondary filter only when two independent filters are necessary; do not rebuild its responsive grid. |
| `inspector-list-panel` | List controls, the list scroll owner, and the filtered empty state. |
| `inspector-source-fact` | Source-like fact card. Set `code` only when the value itself is code, not merely a file location. This treatment is a merge candidate; do not create another source-fact variant. |
| `inspector-source-panel` | Source facts followed by the bounded application stack. Use it as the complete Source tab body instead of rebuilding panel padding or stack placement. |
| `inspector-stack` | Bounded call stack. Pass retained frames, an accurate empty label, and a specific title when showing something other than the application stack. |
| `inspector-workspace` | Shared split, focused, or stream workspace. Use `stream` for a single full-width scrollable list, `top` framing for edge-to-edge sections, and a namespaced `detailId` in focus mode. |
| `popover-surface` | Shared elevated menu surface. Use `anchored` only with Alpine Anchor and choose direction and alignment deliberately. |
| `section-heading` | Restrained title and close description. Do not repeat the tab name or explain an obvious label. |

## Compound families

Some public files have no useful standalone state. They share one Studio page with the parent that gives them meaning:

- `filter-tabs` demonstrates `filter-tab`.
- `inspector-definition-list` demonstrates `inspector-definition-row`.
- `inspector-facts` demonstrates `inspector-fact`.

Keep both files in the catalog family's `members` list so architecture checks still enforce their public dependency boundary. Do not create a second gallery page merely to increase the component count.

## Private section modules

HTTP Client, Cache, Mail, Notifications, Models, Events, Authorization, Queries, Logs, Livewire, and toolbar chrome own product-specific modules. Their complete workspaces, row renderers, data panels, state coordinators, and tab definitions are integration surfaces, not design-system components.

Keep those modules out of normal Studio navigation. Verify them in realistic populated product sections. A private module may use a small demo fixture in a focused test, but it must not gain a public Studio page solely because it is a Blade component.

Private modules should contain only domain decisions:

- labels and filters;
- list-column tracks and row content;
- tab order and deliberate defaults;
- captured evidence and empty-state wording;
- section state and actions.

They should reuse the shared field, badge, fact, source, code, explanation, and workspace grammar rather than reproduce its markup.

## State and composition

- Stateful shared patterns receive explicit expressions, references, labels, and actions from their parent. They do not create a second root store.
- `inspector-workspace` owns split/focus/stream geometry; `inspector-list-panel` owns split-list scrolling; `inspector-detail-pane` owns detail scrolling. In stream mode, the workspace body is the only desktop scroll owner.
- `inspector-detail-tabs` supplies the shared segmented container while the private section supplies tab labels, order, availability, and active state.
- `inspector-explanation` is appropriate only when captured evidence needs interpretation or a conditional next check.
- Code and evidence components receive retained values. They do not infer a source, result, or problem from adjacent data.

## Adding or changing a component

In one change:

1. Decide whether the work belongs to a shared component or a private section module.
2. Reuse or edit the canonical shared component when its semantics match.
3. If a new shared component is warranted, add it to the explicit `StudioCatalog` allowlist, one focused demo, and this reference.
4. If it is an inseparable child, add it to the parent's compound `members` rather than creating a weak standalone page.
5. Keep private modules beside their owning section and out of Studio.
6. Migrate every intended consumer and delete the superseded implementation in the same vertical slice. Do not leave old and new treatments in parallel.
7. Extend architecture and focused behavior tests.
8. Inspect the shared component in Studio and the real section at desktop and mobile widths in both themes.

The architecture test must prove that every Blade component is either public or owned by exactly one private product area, every catalog family has exactly one demo, public components never depend on private modules, and private components never depend on another area's modules. It must not assert that every Blade file is public.
