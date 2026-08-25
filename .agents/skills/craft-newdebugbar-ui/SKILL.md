---
name: craft-newdebugbar-ui
description: Design, build, refactor, or review the New Debug Bar interface in this repository. Use for inspector sections, debug-bar chrome, shared Blade components, responsive behavior, visual hierarchy, diagnostic copy, component extraction, or Studio catalog work. This skill encodes the project's established UI decisions and required browser QA.
---

# Craft New Debug Bar UI

Create calm, truthful Laravel debugging interfaces that help a developer decide what happened and what to inspect next.

## Required reading

Before changing UI:

1. Read the repository `AGENTS.md`.
2. Read [references/design-language.md](references/design-language.md) completely.
3. Read [references/components.md](references/components.md) when choosing, changing, or adding a component.
4. Read [references/verification.md](references/verification.md) before verification.

Use `/__newdebugbar/studio` as the living component catalog. It is a fast way to inspect the shared language, but it does not replace checking the real section with realistic profile data.

## Workflow

### 1. Inspect the real surface first

- Start with the built-in browser.
- Use the real benchmark route and current populated profile when available.
- Record the current desktop, mobile, light, and dark behavior before changing it.
- Inspect the capture and presentation data before deciding what the interface can truthfully say.
- If the request refers to an earlier decision, inspect the relevant task history instead of reconstructing it from memory.
- Find prior work by searching task titles for `New Debug Bar` plus the section name, then read the most relevant task before changing the established contract.

### 2. Decide what the developer needs

Answer these questions before choosing a layout:

- What happened?
- Is anything wrong or merely noteworthy?
- Why does it matter?
- Where did it begin in application code?
- What is the smallest useful next inspection?

Lead with identity or result, then compact facts, then deeper evidence. Do not invent a finding, cause, source, or recommendation that the captured data cannot prove.

### 3. Simplify before adding

Delete first when content repeats information, explains an obvious label, exposes framework noise, or adds a second visual treatment for the same fact.

Preserve unique diagnostic evidence. Move raw, internal, or supporting evidence deeper instead of deleting it when it can answer a real debugging question.

### 4. Reuse the interface grammar

- Use list/detail workspaces for multiple items.
- Use a focused reading view for a single item.
- Use a separate mobile detail step with a clear Back action.
- Use the shared field, tab, badge, fact, source, code, explanation, and workspace components.
- Extract a component when the same visual or interaction rule appears in more than one section.
- Let each section's data decide its labels, filters, tabs, and evidence. Shared structure does not mean identical content.

Check [references/components.md](references/components.md) before creating a new primitive. Add every new reusable Blade component to `StudioCatalog`, its bounded Studio demo, and this reference in the same change.

### 5. Make state deliberate

- Pick a useful default tab and filter from the data model.
- Do not make the developer configure a view before it becomes useful.
- Keep selection and loading changes from shifting nearby layout.
- Give the view one vertical scroll owner.
- Keep actions near the evidence they affect.
- Render expensive details only for the active item or tab.

### 6. Write only useful interface copy

- Use plain language and concrete nouns.
- Titles should answer the question the content addresses.
- Descriptions should explain domain-specific or ambiguous meaning and, when useful, a conditional next check.
- Do not explain self-evident fields such as Source, URL, Status, or Time.
- Do not repeat the tab name or table heading in prose.
- Avoid declaring a problem unless the evidence proves one.

### 7. Verify as product work

Follow [references/verification.md](references/verification.md). At minimum, verify realistic data at short and tall desktop sizes, a mobile viewport, light and dark themes, focus, overflow, and the refresh or reopen flow.

Perform one additional visual and usefulness pass after the implementation appears complete. Inspect fine gaps, alignment, stable columns, scroll ownership, wording, and whether every visible fact helps a developer act.

## Delivery rules

- Keep host-facing identifiers namespaced.
- Keep package JavaScript bounded and page-safe.
- Update focused tests with the component or section.
- Build compiled assets when class extraction or JavaScript changed.
- State exactly what was observed in the browser. Do not call source inspection proof of rendered behavior.
