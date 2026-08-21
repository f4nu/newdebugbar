# Livewire section specification

Status: Proposed for implementation

## Product decision

Add one top-level **Livewire** section with exactly two tabs:

1. **Activity** is the default tab. It shows the meaningful Livewire work observed on the current page in time order.
2. **Components** shows the mounted component instances, their current public state, recent activity, and the property values that can be changed.

The first tab is intentionally called **Activity**, not **Events**. A Livewire event is only one kind of work. Actions, property updates, requests, validation, renders, redirects, downloads, streams, and failures also belong in this timeline. Calling all of them events would make the interface harder to understand and would conflict with New Debug Bar's existing Laravel Events section.

There is no Overview tab. The section header and selected activity provide the useful summary without adding another place to visit.

## User goal

The section should let a Laravel developer answer these questions without reading a raw Livewire payload:

- What just caused the page to change?
- Is anything still running?
- Which component instances took part?
- Which properties changed, and what are their current values?
- Did the component render, skip, fail validation, redirect, download, or fail?
- Which Livewire events were dispatched, and which recipients were actually observed?
- Can I try a different property value on the component that is mounted now?

The product promise is: **follow a Livewire interaction from trigger to visible result, then inspect the exact component involved.**

## Starting point

New Debug Bar currently profiles host Livewire HTTP requests as generic requests. It deliberately does not store Livewire snapshots or expose a dedicated Livewire section. This feature therefore needs a new, bounded capture contract. It is not only a view change.

The new work must preserve these existing contracts:

- Do not store raw Livewire snapshots.
- Do not change host response bodies, response types, status codes, redirects, downloads, or application headers.
- Do not profile New Debug Bar's own Livewire toolbar requests.
- Do not add settings for behavior that can have one fixed product default.
- Keep stored profiles and browser traces bounded. Show when evidence was dropped or is incomplete.

## Scope

### Included

- Current-page Livewire activity, including work that is in progress.
- Stable component instances, not counts grouped only by component name.
- Current public property values for mounted host components.
- Explicit mutation of proven writable property values.
- Links between activity, component instances, and the matching request profile.
- Read-only MCP access to captured Livewire evidence.

### Not included

- Calling arbitrary component methods.
- Replaying or redispatching events.
- Refreshing components from the inspector.
- Time travel or automatic rollback.
- Hot reload.
- Editing historical or unmounted components.
- Editing locked properties, Eloquent models, collections, dates, enums, custom synthesized objects, or values whose write contract is unknown.
- Exposing property mutation through MCP.

These excluded controls can run app code or repeat side effects. They need a separate product decision if they are added later.

## Page-session model

The Livewire section is scoped to the current browser page session, not to one HTTP request. A page session starts on page load and resets after a full navigation or `wire:navigate` navigation.

A single Livewire HTTP request can contain messages from several components, and one user interaction can produce more than one message. Keep these levels separate:

```text
Page session
  Interaction
    HTTP request
      Component message
        Action or property update
        State changes
        Effects
```

The request switcher remains the source of HTTP request truth. A Livewire activity item links to its request profile. Opening a captured Livewire request from the request switcher selects the matching activity when that activity belongs to the current page session.

## Shared section header

The existing inspector header remains unchanged. Inside the Livewire section, show:

- The **Activity** and **Components** tabs on the left.
- A compact live status on the right: `Watching this page`, `Paused`, or `Historical`.
- Small counts for mounted host components and work in progress. Do not add large dashboard cards.

The Livewire navigation item appears when the browser observes at least one host Livewire component or a stored profile contains Livewire activity. The New Debug Bar toolbar component never counts as host activity.

## Activity tab

### Default view

Show interactions from oldest to newest so cause and effect read naturally. Follow the latest item only while the developer is already at the end of the list. If they scroll up, keep their position and show a **Jump to latest** control.

Capture all useful lifecycle evidence, but do not put every low-level callback in the main list. Group related phases into one interaction. The list should stay readable during polling, batching, and rapid input.

Each row shows:

- Status: in progress, complete, skipped, failed validation, failed, cancelled, redirected, downloaded, or streamed.
- A plain-language title such as `Search changed`, `Save review ran`, or `Vendor checked in event dispatched`.
- The primary component name.
- The activity kind: Action, Change, Event, Poll, Mount, Refresh, Lazy load, or Unknown.
- Start time and total duration when both ends were observed.
- A problem marker only when the evidence supports a real problem.

Do not show `/livewire/update`, `__dispatch`, hashes, or raw instance IDs as primary labels.

### Filters

Provide one search field and these compact filters:

- All
- Actions
- Changes
- Events
- Renders
- Problems

Search matches the human component name, class, action, property path, event name, and request path. Filters never change what is captured.

### Detail pane

Selecting an activity keeps the timeline visible on wide screens and opens a detail pane. The detail order is:

1. **What happened**: one sentence that states the trigger and result.
2. **Cause**: observed user action, property update, event receipt, poll, mount, refresh, lazy load, or unknown.
3. **Property changes**: before, submitted, server-confirmed, and current browser values when each one is available.
4. **Components**: every observed component instance that took part, with links to Components.
5. **Events**: source, event name, declared target, and observed recipients.
6. **Result**: render, skip, validation, redirect, download, stream, error, or cancellation.
7. **Timing**: server and browser phases in separate lanes. Never label browser wait as network time or render callback time as paint time.
8. **Request details**: matching profile, message order, source, and bounded technical data behind disclosure.

Every field carries one evidence state: **Observed**, **Inferred**, or **Unknown**. Inferred relationships never replace the facts used to infer them.

### Live behavior

An interaction appears as soon as a documented Livewire interceptor observes it. Its status changes in place as the request, sync, effects, morph, and render phases finish. A failure or cancellation closes the same item rather than adding a second row.

The UI must not announce every phase to a screen reader. Announce the new interaction once, then announce only completion or failure.

### Livewire events

Event rows borrow the useful source-to-event-to-recipient trace from the supplied references, but remain truthful:

- A dispatched target is a declared target.
- A recipient is shown as observed only when a concrete receiving message was captured.
- Bubbling, self dispatch, and targeted dispatch are shown when observed.
- Event data is inspectable as a bounded property tree.
- There is no Replay control.

## Components tab

### Component list

The left pane shows currently mounted host component instances in parent-child order. Each row contains:

- Human component name.
- Parent-child indentation.
- Current status: idle, updating, failed, or stale.
- The most recent meaningful activity.

Provide component search. Search matches the human name, class, source file, and instance ID. Do not add more list filters until real use proves a need.

Components are keyed by stable instance ID. Two instances with the same name remain separate. The New Debug Bar toolbar component is excluded.

If an activity points to an instance that has since unmounted, its detail remains available from that activity as a read-only historical record. It does not stay in the current component list.

### Component detail

The right pane starts with the component's human name. Class, source, view, parent, and instance ID are secondary details with copy actions.

Properties come first. Show one row per serialized public property:

- Property path.
- PHP or synthesized type when known.
- Current browser value.
- Latest server-confirmed value when known.
- State: Synced, Dirty, Updating, Locked, Read only, or Unknown.
- Edit action when the write contract is proven.

Arrays use an expandable property tree. Do not flatten nested values into unreadable JSON by default. Recent activity appears below the properties and links back to Activity.

### Property mutation

Mutation is available only when all of these conditions are true:

- The component is a host component mounted on the current page.
- The server capture proves that the path is a public, unlocked property.
- The current value is a primitive (`null`, boolean, integer, float, or string) or a primitive leaf inside an array.
- The component ID and property value still match the state from when editing began.

Use a control that matches the value type. Strings use text or a textarea, numbers use strict numeric inputs, booleans use a switch, and null requires choosing the replacement type.

Editing creates a local draft. It does not change the component. The detail pane shows:

> Applying sends a real Livewire update and may run property update hooks or other app code.

The primary action is **Apply to component**. Applying uses Livewire's public component API and sends an immediate server update. Do not stage a hidden change for a later unrelated request.

After Apply:

- Show Updating until the matching interaction finishes.
- Show the server-confirmed value on success.
- Keep the draft and show the real error on validation, lock, type, network, or server failure.
- Add the resulting work to Activity and offer **View activity**. Do not switch tabs automatically.
- If the component unmounted or the value changed before Apply, do not overwrite it. Ask the developer to reload the current value.

There is no automatic Undo. Sending the previous value would create another Livewire request and cannot undo hooks or external side effects.

## Wide and narrow layouts

At the normal expanded inspector width:

- Activity uses a two-pane layout with the timeline on the left and detail on the right.
- Components uses a narrower component list on the left and component detail on the right.
- Pane borders, selected rows, type, spacing, and indigo accent reuse the current Requests section and existing New Debug Bar tokens.

On a 390px-wide screen:

- Both tabs fit without horizontal scrolling.
- Lists and details become one-pane drill-ins.
- Selecting an item opens its detail with a clear Back control.
- Draft property edits survive moving between the component list and detail, but not a page navigation.
- No property tree, value, action, or status causes page-level horizontal overflow.

The interface must work in light and dark themes, with keyboard-only use, at 200% zoom, with reduced motion, and with a screen reader.

## Visual direction

The current Requests section is the visual baseline:

- Lead with a compact summary.
- Use a clear vertical trace for sequence.
- Use plain labels and restrained status color.
- Keep raw details behind disclosure.
- Use rounded surfaces, thin borders, and the existing indigo selected state.

Borrow these ideas from the supplied references:

- A persistent component list beside selected component data.
- A property tree that preserves types.
- A source-to-event-to-recipient trace.
- Separate client and server values when both are known.

Do not copy their dense browser-DevTools chrome, neon type blocks, large tab collections, replay controls, or time-travel controls.

## Capture and data contract

Use Livewire 4's documented public browser APIs as the main browser boundary:

- Global action, message, and request interceptors for the activity lifecycle.
- `Livewire.all()` and `Livewire.find(id)` for mounted component access.
- Component initialization and cleanup hooks for the live registry.
- The public property setter for an approved mutation.

An isolated server boundary records component descriptors, message order, actions, property updates, state diffs, effects, and proven application work. Any use of a Livewire internal contract must live in this boundary and have a focused compatibility test.

Store bounded diffs and causal records, not raw snapshots or complete historical state trees. Current property inspection reads from the mounted browser component. In-progress browser records remain local until they can be matched to a saved request profile.

Browser trace appends must be same-origin, profile-ID validated, token checked, bounded, atomic, and failure-isolated. Register interceptors once and remove them on cleanup so `wire:navigate` never accumulates duplicate listeners.

Suggested record shape:

```text
livewire
  page_session_id
  interactions[]
    id, sequence, kind, status, title
    started_at, finished_at
    request_profile_ids[]
    component_ids[]
    message_ids[]
    state_change_ids[]
    event_ids[]
    result, completeness
  components[]
    id, name, class, source, view
    parent_id, depth, mounted
    property_descriptors[]
  state_changes[]
    id, interaction_id, component_id, path, type
    before, submitted, server, browser
    confidence, completeness
  events[]
    id, interaction_id, source_component_id
    name, mode, declared_target
    observed_recipient_ids[]
  browser_phases[]
  server_phases[]
  dropped_counts
```

Exact collection caps belong in fixed internal constants, not public config. When a cap is reached, keep the retained evidence and show the exact dropped count.

## MCP contract

The MCP server remains read-only. It exposes the captured interaction, component metadata, state diffs, event links, timing, completeness, and stable IDs so coding agents can follow the same evidence as the interface.

MCP does not receive current browser-only property values and cannot mutate a component. It must say when a value or phase exists only in the live browser.

## Required states

| State                                   | Required result                                                                                                    |
| --------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| No host Livewire found                  | No Livewire navigation item.                                                                                       |
| Host components mounted, no updates yet | Components is populated. Activity shows the observed mount or a calm empty state if mount evidence is unavailable. |
| Work in progress                        | One live activity row updates in place.                                                                            |
| Batched request                         | One interaction can link to several separate component messages and instances.                                     |
| Validation failure                      | The activity and property show the failed fields without treating the HTTP 200 response as success.                |
| Renderless or skipped message           | Show the result without creating a warning by default.                                                             |
| Missing browser evidence                | Keep server evidence and label missing phases Unknown.                                                             |
| Missing server evidence                 | Keep browser evidence and label server facts Unknown.                                                              |
| Truncated capture                       | Show retained records and the dropped count.                                                                       |
| Component unmounted                     | Historical detail is read only. Current component row is removed.                                                  |
| Locked or unsupported property          | Value is inspectable when available and cannot be edited.                                                          |
| Mutation succeeds                       | Value is server-confirmed and the matching activity is linked.                                                     |
| Mutation fails                          | Draft remains, error is shown, and no success state is claimed.                                                    |

## Acceptance criteria

1. The section has exactly two tabs named Activity and Components, with Activity selected first.
2. The main timeline groups related Livewire phases into meaningful interactions and shows in-progress work without duplicating rows.
3. Every activity detail separates observed, inferred, and unknown facts.
4. Events distinguish declared targets from recipients that were actually observed.
5. The component list represents stable mounted instances and excludes New Debug Bar's own component.
6. Component detail shows current public state without storing a raw Livewire snapshot.
7. Typing a draft never mutates the host component. Only Apply to component sends an update.
8. Locked, historical, stale, model, collection, date, enum, custom object, and unknown properties remain read only.
9. A successful or failed mutation produces one linked Activity interaction.
10. No method call, replay, refresh, hot reload, time travel, or MCP mutation control ships with this work.
11. Host response bytes and behavior remain unchanged apart from bounded debug-only correlation headers.
12. Capture remains bounded, duplicate listeners do not accumulate, and incomplete evidence is visible.
13. MCP can read the same stored causal links and clearly marks browser-only gaps.
14. Each Blade file stays under 500 lines. Repeated controls become package components.
15. Focused PHP and JavaScript tests cover collection, correlation, component identity, cleanup, mutation eligibility, stale edits, mutation results, response safety, bounds, and malformed evidence.
16. Browser checks use the canonical Livewire example app without changing it to satisfy package tests.
17. Browser verification covers real search, action, validation, event, nested component, unmount, and property-mutation flows in light and dark themes, by keyboard, with a clean console, and at 390px.

## Build order

1. Define the page-session, interaction, component, state-change, and evidence contracts.
2. Add bounded browser and server capture with response-safety tests.
3. Build the read-only Activity and Components views.
4. Add primitive property drafts and explicit Apply behavior.
5. Add MCP presentation for stored evidence.
6. Complete browser, visual, accessibility, performance, and minimum-version checks.

## Primary Livewire references

- [JavaScript and documented interceptors](https://livewire.laravel.com/docs/4.x/javascript)
- [Public properties, supported types, mutation, and locked values](https://livewire.laravel.com/docs/4.x/properties)
- [Actions and property updates](https://livewire.laravel.com/docs/4.x/actions)
- [Event dispatch modes](https://livewire.laravel.com/docs/4.x/events)
