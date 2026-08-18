# Livewire diagnostics contract

This document records the evidence rules for the clean-sheet Livewire section. It is an implementation contract, not a promise that every Livewire request exposes every field.

## User task

A Livewire developer must be able to answer:

1. What triggered this request?
2. What important server work ran?
3. What state changed?
4. Which component instances rendered, and why?
5. What events or effects were observed?
6. Where did server and browser time go?
7. What evidence is missing?
8. What should be inspected next?

The package records facts before findings. It does not invent a single interaction, a full page component tree, an event recipient, a causal link, network time, or paint time.

## Baseline

- Three-tab redesign starting commit: `d7ff8353ea2bd62dac13c61f93feb10a867909ca`.
- Global profile schema: version 1.
- Livewire support floor: 4.1.0. Installed contract check: 4.3.5.
- Livewire 4.1.0 and 4.3.5 both expose public request, message, and action interceptors. The server lifecycle event bus, the debug-only `profile` event, and `action.origin` are internal contracts.
- Livewire 4.1 can surface a stale current component as a root mount parent while 4.3 reports no parent. A parent link is retained only when that instance was already observed in the same exchange.
- Baseline package checks on 2026-08-10: 140 PHP tests passed with 1,221 assertions and 2 expected skips; 36 JavaScript tests passed; the production asset build passed.
- The canonical Livewire example rendered cleanly and a real search update completed with a clean browser console. That checkout used its public package dependency, so it is baseline product evidence rather than proof of this worktree.

The final local Testbench microbenchmark ran three times with 30 property-update requests per lane on PHP 8.5.7, Laravel 13.24.0, and Livewire 4.3.5. The disabled medians were 1.023–1.030 ms; enabled medians were 1.883–1.902 ms; measured median deltas were 0.856–0.876 ms. Disabled p95 was 1.089–1.144 ms and enabled p95 was 2.007–2.059 ms. This is repeatable local evidence, not a production latency promise or an invented pass/fail budget.

## Final verification

- The current stack used PHP 8.5.7, Laravel 13.24.0, and Livewire 4.3.5. The package suite passed 184 tests with 1,688 assertions. The full browser suite passed 114 tests with 1,342 assertions.
- The JavaScript suite passed 50 tests. Overall line, branch, and function coverage was 91.57%, 86.23%, and 95.15%. The production build, strict Composer validation, dependency audit, formatting check, and Git whitespace check passed.
- An isolated dependency lane resolved the declared floor of PHP 8.1, Laravel 10.50.2, and Livewire 4.1.0. Its full package suite completed 186 tests with 1,437 assertions and no failures. The available runtime binary was PHP 8.5.7, so the older dependency lane reported dependency deprecations and is dependency-resolution proof, not an actual PHP 8.1 runtime run.
- Four planned Livewire visual baselines passed after manual inspection: desktop light and dark, plus 390px light and dark. The full visual suite passed without changing unrelated baselines.
- The canonical Livewire example used a reversible vendor-only link to this worktree without source or Composer changes. Its workspace feature group passed 5 tests with 47 assertions. Real search and check-in interactions showed the three tabs, `Application Board`, `Search changed`, safe property changes, and separate declared and observed event recipients. The browser console was clean. The local check-in was undone and the public package dependency was restored.
- Response-safety tests cover exact response bytes, payload, application headers, status, redirects, and downloads. Browser trace headers are the only intentional response additions for an eligible profile.
- No setting, database, migration, editor, replay, component-refresh, event-redispatch, hot-reload, or browser-extension change was added.

## Primary research

- Livewire's documented JavaScript interceptors are the browser capture boundary: <https://livewire.laravel.com/docs/4.x/javascript>.
- Actions document that `$refresh` sends a request and applies pending state, so an inspector refresh control could change the host app: <https://livewire.laravel.com/docs/4.x/actions>.
- Events document global, targeted, and self dispatch. Any redispatch control could invoke listeners, network work, and application side effects: <https://livewire.laravel.com/docs/4.x/events>.
- Deferred and live property updates are normal behavior: <https://livewire.laravel.com/docs/4.x/wire-model>.
- Polling, validation failures, and renderless actions are normal documented behavior and are not findings by themselves: <https://livewire.laravel.com/docs/4.x/wire-poll>, <https://livewire.laravel.com/docs/4.x/validation>, and <https://livewire.laravel.com/docs/4.x/attribute-renderless>.
- Public properties are serialized between requests, while lazy loading and islands are documented tools for independent display work: <https://livewire.laravel.com/docs/4.x/properties>, <https://livewire.laravel.com/docs/4.x/lazy>, and <https://livewire.laravel.com/docs/4.x/islands>.
- Class, single-file, and multi-file component layouts inform the short human component names: <https://livewire.laravel.com/docs/4.x/components>.
- The exact installed Livewire 4.3.5 source confirms that debug-only profile timings and `__dispatch` are internal mechanics, not product labels: <https://github.com/livewire/livewire/blob/7ef4b2a876c71744e86463079dd506b26eeab624/src/Mechanisms/HandleComponents/HandleComponents.php>.
- Laravel response contracts require preserving response types, bytes, status, headers, redirects, and downloads: <https://laravel.com/docs/13.x/responses>. Debug-only evidence follows Laravel's documented `app.debug` boundary: <https://laravel.com/docs/13.x/configuration>.

## Source order

Use the strongest available source and keep its provenance:

1. `package`: IDs, bounds, correlation, file storage, and request/response facts owned by New Debug Bar.
2. `livewire_public`: documented Livewire request, message, action, snapshot, and interceptor callbacks.
3. `livewire_internal`: one isolated, contract-tested server or browser gateway. Missing evidence must fall back cleanly.
4. `inferred`: a label or relationship derived from unambiguous observed facts. The input evidence and confidence must remain visible.
5. `unknown`: the package cannot prove the value.

Confidence is one of `observed`, `inferred`, or `unknown`. An inferred fact never replaces the observed facts used to derive it.

## Evidence map

| Field or group | Source | Availability | Clock | Privacy | Bound | Fallback |
| --- | --- | --- | --- | --- | --- | --- |
| Profile ID | Package UUID v4 | Every stored profile | None | Safe identifier | One | Profile is not stored if invalid |
| Profile revision | Package store | Every Livewire profile | None | Safe integer | Starts at 1 and only increases | Reject stale append |
| Exchange ID | Package UUID v4 | Livewire mount or eligible update | None | Safe identifier | One per HTTP request | No Livewire section without activity |
| Request method, path, status, content type | Laravel request and response | Every exchange | Server | URL query is redacted | One | `unknown` for unavailable response facts |
| Request and response bytes | Laravel request and final response | When countable | Server | Size only | One value each | `null` for an uncountable stream |
| Message ID and request order | Package over Livewire component payload order | Every valid update message | None | Safe identifier | Capped by Livewire and package limits | Mark request partial if a message is malformed |
| Component instance ID and name | Documented Livewire snapshot memo | Mounts and updates | None | Mount-scoped identifier | One per message or mount | Unknown component; never merge by name |
| Component class | Isolated server gateway component object | When lifecycle hooks run | Server | Class name only | One | `unknown` |
| Component source and view | Reflection plus isolated render view object | Class, single-file, or multi-file source when resolvable | Server | Project-relative file and line only | One source and one view | `unknown`; no editor URL |
| Parent ID, key, and depth | Isolated mount hook | Initial mounts observed in this request | Server | Identifiers only | Observed affected mounts | Unknown; never call the result a full page tree |
| Page or mount scope | Snapshot path and mount-scoped instance ID | When present | None | Redacted path | One | `unknown` |
| Action ID, name, and parameters | Documented action/message contract plus request payload | Each call | Browser and server | Parameters pass through the Redactor | Capped calls, depth, items, and strings | Keep name with hidden or truncated parameters |
| Property update path and submitted value | Documented message payload | Each submitted update | Browser to server | Secret paths show changed/hidden; other values are redacted | Capped paths and values | Record path with unavailable value |
| Before and server state | Isolated server lifecycle component state | Hydrated and dehydrated component state | Server | Diff only; secret values never stored | Changed paths only, bounded | `unknown` layer |
| Browser state | Documented message success and sync callbacks | Browser trace appended | Browser | Equality, type, and presence only; no raw browser values | Changed paths only | `unknown` or `missing` trace |
| Trigger kind | Observed updates, calls, metadata, and event call shape | Every valid message | None | Names and redacted parameters | One taxonomy value per action | `unknown` |
| Request activity label | Package inference | Only one unambiguous property, action, poll, refresh, or received event | None | Uses safe component and action labels | One | “Livewire request” plus observed facts |
| Rendered or skipped | Response effects, render hook, and documented skipped callback | Per message when observed | Server or browser | No HTML stored | One state | `unknown` |
| Render reason | Package inference from the message's observed triggers | Only when one reason is defensible | None | Safe labels | One per component | `unknown`, never “because” without proof |
| Validation fields and errors | Snapshot errors, action return metadata, and Laravel validation collector | When emitted | Server | Field names and redacted messages | Existing collector bounds | Empty observed list or unknown |
| Redirect and download | Documented Livewire response effects and request callbacks | When emitted | Server and browser | Safe destination or filename only | One effect per message unless Livewire returns more | Unknown effect details |
| Stream | Documented request stream callback | Browser trace only | Browser | Chunk metadata only; no content | Bounded count | Partial or missing trace |
| Event source | Component whose response contains a dispatch effect | Server dispatch observed | Server | Component ID and safe name | One | `unknown` |
| Event name and declared target | Serialized Livewire dispatch effect | When emitted | Server | Payload redacted | Bounded events and payload | Keep name with unknown target |
| Observed event recipient | A later `__dispatch` call on a concrete component message | Only for that receiving exchange | Server | Recipient component ID | Observed recipients only | `unknown`; declared target is not an observed recipient |
| Server span | Internal debug-only `profile` event through the gateway | `app.debug=true` and contract available | Livewire wall-clock range normalized to the exchange | Phase, component, and duration only | Bounded spans | Missing span with `unknown` duration |
| Browser request wait | Documented request send/response callbacks | Browser trace appended | Browser monotonic | Timing only | One span per request | Missing or partial trace; never call it network time |
| Sync, effects, morph, render callback | Documented message callbacks | Browser trace appended | Browser monotonic | Timing only | Bounded spans | Missing or partial trace; render callback is not paint |
| Queries, models, cache, events, logs, views, mail, and queue links | Active package execution-context stack | Only while a proven component/action phase is active | Server | IDs and phase only | One compact context object per collector item | No Livewire link |
| Findings | Livewire analyzer over stored facts | Only for defensible non-duplicate rules | None | Evidence IDs, not raw state | Existing findings cap | No finding |
| Trace status | Package plus append result | Every Livewire section | Both kept separate | Safe enum | One | `missing`, `partial`, `complete`, or `expired` |
| Completeness and truncation | Package counters and gateway availability | Every Livewire section | None | Counts only | One flag set per collection | Explicit unknown or partial evidence |

## Section schema

The global profile stays at schema version 1. A Livewire profile adds `sections.livewire.schema_version = 1`.

```text
sections.livewire
  schema_version
  profile_revision
  exchange
    id, request_id, kind, title, title_confidence
    result, status, request_bytes, response_bytes
    server_clock, browser_clock
  messages[]
    id, request_index, component_id
    actions[], state_changes[], result, caused_by[]
  actions[]
    id, message_id, component_id
    kind, name, parameters, property_paths
    source, confidence, caused_by[]
  components[]
    id, mount_scope, name, class, source, view
    parent_id, key, depth, rendered, render_reason
    completeness
  state_changes[]
    id, action_id, component_id, path, type
    before, submitted, server, browser
    redacted, confidence
  events[]
    id, source_component_id, name, mode
    declared_target, observed_recipient_ids, recipient_status
  server_spans[]
    id, component_id, action_id, phase, start_ms, duration_ms
  browser_trace
    status, appended_at, spans[], failures[]
  findings[]
  completeness
    messages, components, state, events, server_spans, browser_trace
    truncated, dropped_counts, unknown_reasons[]
```

Raw Livewire snapshots are never stored in this section and are never returned by MCP.

The MCP section read returns a paginated list of typed causal records. Messages, actions, components, state changes, events, server spans, and browser spans keep their stable IDs and links. Action and event parameters are omitted. State-change records include the path, type, redaction status, and browser equality only; before, submitted, server, and browser values are never included.

## Taxonomies

Trigger kinds:

- `initial_mount`
- `property_update`
- `action`
- `event_received`
- `poll`
- `refresh`
- `lazy_load`
- `island`
- `unknown`

Message results:

- `rendered`
- `renderless`
- `skipped`
- `validation_failed`
- `redirected`
- `downloaded`
- `streamed`
- `failed`
- `cancelled`
- `unknown`

Render reasons use the same observed trigger language. Multiple possible causes produce `unknown` instead of a guessed primary cause.

## Gateway contracts

- Public browser interceptors are registered once globally and return unsubscribe callbacks.
- `action.origin` is optional. Its directive and element metadata are read only by the browser gateway and are dropped when its contract is unavailable.
- Server lifecycle events and debug-only profile timings are read only by the server gateway. Their signatures are covered by real Livewire mount and update tests.
- The execution context is a stack. Every scoped operation unwinds in `finally`, and request finalization clears any remaining frames.
- No Livewire method, request, component, response, DOM node, or effect is monkey-patched.

## Interface state matrix

The section has exactly three left-aligned tabs: Overview, Components, and Events. Hidden panels remain in the rendered profile so keyboard and browser checks can switch between them without fetching a second interpretation.

| State | Real fixture or evidence | Required visible result |
| --- | --- | --- |
| Initial mount | Nested parent and child mount | Mount activity, affected-only explanation, stable instance IDs, parent link |
| One property update | `search` update | `Search changed`, safe before-to-server diff, optional browser equality |
| Named action | `saveReview` | Human action name, component, state change, render result |
| Multi-action batch | 17 distinct messages | Familiar activity label, affected component rows, no automatic warning |
| Nested subcomponents | Parent and child fixture | Affected relationships, never a claimed full page tree |
| Emitted event | `vendor-checked-in` dispatch | Source, declared target, recipient unknown when not observed |
| Received event | `__dispatch` on child | Concrete observed recipient without inventing a source |
| Validation failure | Empty `search` validation | Validation result and affected field |
| Redirect | Redacted vendor redirect | Redirect result and safe destination |
| Download | Text report effect | Name, type, and size only; no content |
| Renderless or no-op | Renderless heartbeat | Completed without a render; no automatic warning |
| Missing browser trace | Server-only profile | Visible missing status and a safe next check |
| Partial browser trace | Unmatched callback evidence | Visible partial status; unmatched facts stay unknown |
| Truncated evidence | Collector bound exceeded | Retained facts plus explicit truncation notice |
| Redacted change | Secret property update | Changed and hidden; no before, submitted, or server secret |
| Empty events | Request with no dispatch | Events tab shows a calm empty state without a low-value counter |
| Corrupt or partial profile | Missing or wrong-shaped optional fields | Generic unknown labels and visible evidence gaps, never a render failure |

Overview leads with clear problems. Each finding shows the problem, impact, origin, and next check. With no supported finding it shows `No clear problem found`. Components prioritizes the selected component's name, trigger, safe changes, result, validation, events, server work, then source details. Events stays chronological and never presents a declared or likely recipient as observed.

Polling is inferred only when one correlated refresh has an observed `wire:poll` source. A browser-skipped callback is shown beside, and never replaces, the server result. Parallel and out-of-order requests keep separate request objects, profile IDs, append tokens, and revisions.

## Known unknowns and deferred scope

- A conceptual interaction may span several requests. Deep multi-request correlation is deferred.
- The affected component relationship is not a full page inventory or full component tree.
- Actual event recipients are unknown unless a concrete receiving message is observed.
- Browser request wait includes work between browser callbacks. It is not exact network time.
- The render callback is not paint time.
- Upload and stream content are not captured. Deep upload and stream correlation is deferred.
- Broad MCP filters and broad findings are deferred until the core model proves a need.
- Component refresh and event redispatch are not shipped. Livewire documents that they can commit pending state, invoke listeners, start network work, or repeat application side effects. Keep them out until a narrower host-safe contract exists.

## Running decisions

| Decision | Reason |
| --- | --- |
| Keep the global schema at version 1 and version the Livewire section separately | The addition is not a top-level breaking change. |
| Use concrete classes and one isolated gateway | This keeps unstable Livewire details in one tested boundary without extra interfaces. |
| Keep browser and server clocks in separate lanes | The clocks cannot be combined into exact network or paint durations. |
| Store state diffs, not snapshots | Developers need changes while profiles and MCP must stay bounded and safe. |
| Keep event declaration and observation separate | A declared target is not proof of a recipient. |
| Treat missing evidence as a visible product state | Silent omission would make the debugger look more complete than it is. |
| Start findings with observed server work at or above 200 ms | A measured wait has a clear impact. Normal validation, polling, renderless work, a 17-item batch, or an unobserved event receiver does not. |
| Offer only `Copy details` and `Copy event` in the far-right action area | These controls copy already prepared bounded text, make no request, and do not mutate or replay host application work. |
| Return typed causal records from MCP | Agents can follow the same IDs as the UI without receiving state values or snapshots. |
