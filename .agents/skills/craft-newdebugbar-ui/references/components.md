# Component catalog

The living visual catalog is `/__newdebugbar/studio`. The source registry is `src/Presentation/StudioCatalog.php`. Every file in `resources/views/components` must appear exactly once in the registry and in a bounded Studio demo.

Prefer composition over adding props that only serve the Studio. State-bound components should be demonstrated inside the smallest realistic parent harness.

## Foundations

| Component                   | Use                                                                                                                                                |
| --------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| `icon`                      | Render a package-owned SVG by name and explicit supported size. Do not use an icon when text is clearer.                                           |
| `icon-button`               | Accessible icon-only action. Always provide an accessible name; use `darkSurface` only on dark chrome and `colorOnly` for quiet text-like actions. |
| `inspector-action`          | Compact labeled action inside a detail pane. Keep it contextual to nearby evidence.                                                                |
| `inspector-operation-badge` | Neutral equal-width HTTP method or cache-operation badge. Use `wide` for longer operations and `outlined` in detail headers.                       |
| `search-field`              | Shared labeled search input. The normal icon position is left.                                                                                     |
| `select-field`              | Shared native select with stable field geometry. Use for a single list filter rather than a segmented strip.                                       |
| `filter-tab`                | One tabs or segmented option. Place only inside `filter-tabs`; express selection with `aria-selected` or `aria-pressed`.                           |
| `filter-tabs`               | Accessible tabs or segmented-control group. Give it a concrete label.                                                                              |
| `empty-state`               | Calm no-results or section-empty message. `success` is only for genuinely positive empty states.                                                   |
| `popover-surface`           | Shared elevated menu surface. Use `anchored` only with Alpine Anchor and choose direction and alignment deliberately.                              |
| `theme-menu-item`           | Contextual action that offers the opposite of the resolved light or dark theme. Relies on root theme state and closes the active mobile menu.      |
| `theme-toggle`              | Compact light/dark theme control for the toolbar.                                                                                                  |
| `section-heading`           | Restrained title and close description for a section. Do not repeat the tab name in the description.                                               |
| `code-block`                | Syntax-highlighted code or data. Pass the real language; never use it for an ordinary path or label.                                               |

## Inspector structure

| Component                   | Use                                                                                                                            |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `inspector-definition-list` | Stack definition rows with one divider system.                                                                                 |
| `inspector-definition-row`  | One label/value pair. Use `danger` only for an actual failed or harmful state.                                                 |
| `inspector-detail-back`     | Mobile drill-in Back action; `persistent` also exposes it on desktop when the flow truly needs it.                             |
| `inspector-detail-empty`    | Center a short selection instruction in an unselected detail pane.                                                             |
| `inspector-detail-header`   | Stable selected-item identity and optional actions. Use `grid` for fixed action placement and `wrap` for long identities.      |
| `inspector-detail-pane`     | Detail scroll owner with mobile drill-in behavior. Supply the real open state, reference, label, Back label, and close action. |
| `inspector-detail-tabs`     | Detail segmented tabs. Center by default; use `left` only when adjacent controls require it.                                   |
| `inspector-evidence`        | Optional label plus syntax-highlighted evidence. Choose the actual language.                                                   |
| `inspector-explanation`     | Friendly help for ambiguous evidence and a conditional next check. Do not use for obvious labels.                              |
| `inspector-fact`            | One compact labeled fact. Place inside `inspector-facts`.                                                                      |
| `inspector-facts`           | Responsive fact tracks. Use two to four columns and disable its border only when the parent already supplies the divider.      |
| `inspector-list-panel`      | List controls, list scroll owner, and filtered empty state.                                                                    |
| `inspector-source-fact`     | Source-like fact card. Set `code` only when the value itself is code, not merely a file location.                              |
| `inspector-source-link`     | Underlined application source action with no icon, padding, or hover fill.                                                     |
| `inspector-stack`           | Bounded application call stack. Pass retained application frames and an accurate empty label.                                  |
| `inspector-workspace`       | Shared split or focus workspace. Use `top` framing for edge-to-edge sections and a namespaced `detailId` in focus mode.        |

## Toolbar and navigation

| Component                | Use                                                                                                |
| ------------------------ | -------------------------------------------------------------------------------------------------- |
| `corner-toolbar`         | Complete quiet request toolbar. Demonstrate or test inside the root debug-bar Alpine state.        |
| `mobile-request-metrics` | Most useful query, duration, and memory metrics on narrow screens. Supply the correct state scope. |
| `mobile-toolbar-popover` | Accessible mobile menu shell. Use a namespaced ID and explicit label.                              |
| `request-option`         | One saved request choice with stable identity and outcome facts.                                   |
| `request-switcher`       | Current-request trigger and picker; depends on root request history state.                         |
| `toolbar-anchor-preview` | Drop-target preview while moving the toolbar. Supply one valid placement.                          |
| `toolbar-button`         | Toolbar metric or section summary that opens an inspector section.                                 |
| `window-controls`        | Expand, shrink, and close group. Use `darkSurface` only on dark chrome.                            |

## HTTP Client

| Component                    | Use                                                                                  |
| ---------------------------- | ------------------------------------------------------------------------------------ |
| `http-client-controls`       | Count, search, and compact status filter in the list header.                         |
| `http-client-detail`         | Selected-request coordinator. It owns active Response, Request, and Source states.   |
| `http-client-detail-tabs`    | Response-first detail tabs using the shared segmented treatment.                     |
| `http-client-empty`          | No-selection instruction for outbound requests.                                      |
| `http-client-header`         | Method badge and URL identity only.                                                  |
| `http-client-list-item`      | One stable row for method, URL, status or failure, and duration.                     |
| `http-client-no-response`    | Accurate no-response state for connection failures; do not fabricate response facts. |
| `http-client-request-panel`  | Host, request headers, and request body.                                             |
| `http-client-response-panel` | Status, duration, response headers, and body.                                        |
| `http-client-source-panel`   | Application initiation source and bounded stack.                                     |
| `http-client-workspace`      | Full outbound-request list/detail composition. Pass retained items and summary.      |

## Cache

| Component              | Use                                                                                  |
| ---------------------- | ------------------------------------------------------------------------------------ |
| `cache-controls`       | Count, search, and one operation-filter dropdown.                                    |
| `cache-detail`         | Selected cache-operation coordinator.                                                |
| `cache-detail-tabs`    | Overview, Raw, and Source segmented views; omit Raw when it adds no unique evidence. |
| `cache-empty`          | No-selection instruction for cache activity.                                         |
| `cache-header`         | Equal-width operation badge and key on one line.                                     |
| `cache-list-item`      | Compact operation badge and key row; do not show an artificial row ID.               |
| `cache-overview-facts` | Result, store, duration, TTL, and other retained operation facts.                    |
| `cache-overview-panel` | Structured cache-operation overview without duplicate prose.                         |
| `cache-raw-panel`      | Retained raw value or payload only when useful.                                      |
| `cache-source-panel`   | Application source and bounded stack.                                                |
| `cache-workspace`      | Full cache list/detail composition. Pass retained items and summary.                 |

## Mail and notifications

| Component                     | Use                                                                                                                                   |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| `mail-actions`                | Contextual mail actions near the selected message. Do not restore a redundant View email button.                                      |
| `mail-header`                 | Subject and delivery identity for the selected message.                                                                               |
| `mail-message-details`        | Recipients, headers, attachments, and delivery facts.                                                                                 |
| `mail-source-panel`           | Creation source and bounded application stack.                                                                                        |
| `notification-delivery-panel` | Actual outcome for every captured channel and destination.                                                                            |
| `notification-detail`         | Selected notification coordinator for delivery, data, and source views.                                                               |
| `notification-header`         | Notification identity, recipient context, and lifecycle actions. The overall attention state does not become a separate detail badge. |
| `notification-payload-panel`  | Application notification data without queue-internal noise.                                                                           |
| `notification-source-panel`   | Initiation source and bounded stack.                                                                                                  |

Notification components read the parent inspector state. The parent must provide `selectedNotification`, `notificationDetailOpen`, `notificationDetailTab`, `notificationChannel`, and `selectedNotificationDelivery`, plus `setNotificationDetailTab`, `setNotificationChannel`, `openNotificationMail`, `openRelatedProfile`, and the retained-evidence formatter. Keep Delivery first. On a narrow viewport, the current detail tabs use icon-only controls with full `aria-label` values.

## Framework evidence

| Component                  | Use                                                                                                                            |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `authorization-detail`     | One authorization result with ability, actor, subject, callback, and source evidence.                                          |
| `event-detail`             | One event separated into overview, payload, and source evidence.                                                               |
| `livewire-property-editor` | Supported scalar property editor with draft, validation, mutation, and shortcut states.                                        |
| `livewire-split-view`      | Stable Livewire list/detail geometry across desktop and mobile.                                                                |
| `log-entry`                | Structured log item with severity, message, context, source, and occurrences. It teleports into `#newdebugbar`.                |
| `model-group`              | One model class row with retrieved, write, and extra-retrieval counts. Drivers and table names stay in the interface typeface. |
| `model-group-detail`       | Records-first selected-model detail, with writes and application sources as separate useful views.                             |
| `query-actions`            | Contextual SQL copy and explain actions with visible feedback.                                                                 |
| `query-execution`          | One logical execution with SQL, bindings, timing, source, and optional EXPLAIN evidence.                                       |
| `query-section`            | Query filters, grouped SQL rows, and selected execution coordinator.                                                           |

## State-bound component contracts

- Toolbar components run inside the root `newDebugBar(summary, profileLimit)` Alpine state. It owns the current request, request history, selected section, theme, menus, placement, and window actions. Do not create a second root store.
- HTTP Client components run after `initializeHttpClient(items)`. The parent owns `selectedHttpClientRequest`, `httpClientDetailOpen`, `httpClientDetailTab`, search and filter state, selection, and the request formatting and copy helpers.
- Cache components need the retained operation list, `selectedCacheOperation`, `cacheDetailOpen`, `cacheDetailTab`, search and filter state, operation selection, and payload formatting. Use the real section state when possible.
- Mail components need `selectedMailMessage`, `mailDetailTab`, address formatting, preview URL generation, detail-tab selection, and related-profile navigation.
- Authorization and Event detail components read their selected record and active detail-tab state from their section root. Demonstrate them with captured-shape records, not display-only invented props.
- Models components receive a normalized `group`; the section root owns the selected group, Records-first tab, mobile detail state, search, and selection actions.
- Query components receive analyzed query arrays and identities. Copy actions use the shared clipboard helper; EXPLAIN remains a Livewire action and must expose loading, success, and error states.
- `livewire-property-editor` reads a normalized property row plus draft, validation, mutation, focus, and keyboard-shortcut helpers from the Livewire section state. A Studio demo must remain non-mutating.
- `log-entry` receives one normalized `entry`, teleports supporting detail to `#newdebugbar`, and uses the root detail-sequence and copy helpers.

## Adding or changing a component

In one change:

1. Reuse or edit an existing component when its semantics match.
2. If a new component is warranted, keep its API about product semantics, not one page's layout accident.
3. Add it to `StudioCatalog` with a useful description.
4. Add a bounded real demo in the matching Studio family.
5. Document its purpose, important variants, and state dependencies here.
6. Extend completeness and focused behavior tests.
7. Inspect it at desktop and mobile widths in both themes.
