## Why

The backport of the `main` change of the same name, ACE-715, archived there as
`openspec/changes/archive/2026-09-22-ace-715-profile-query-constraint-event`.

A project that needs one more condition on the profiles `academic_persons`
shows has no way to add it on this branch either. The profile controller is
`final`, every helper that builds the repository query is `private`, the uid
lookups dispatch no event, and the existing demand event cannot carry a
condition the repository does not know. Two of the six projects of the
2026-09-12 differences analysis carry their own copies of the controller and
the repositories for exactly this, and both of them run 2.3.4 - so they can
only drop those copies once something on **this** branch replaces them.
Waiting for 3.0 means carrying them through the upgrade instead of before it.

## What Changes

- New PSR-14 events let a listener add query conditions to the profile query
  of the list, list-and-detail and card plugins, to the profile uid lookup of
  the selected-profiles plugin, and to the contract uid lookup of the
  selected-contracts plugin.
- The added conditions are combined with the plugin's own ones and applied
  before pagination, so page counts reflect them. Orderings stay with the
  extension.
- **Nothing is breaking.** No repository signature changes, and the two uid
  lookups keep both their signature and their callers. A project that
  overrides one of them keeps the behaviour it has today.

The behaviour is identical on TYPO3 v12 and v13.

## What is deliberately left out

- **The plugin context.** On `main` the events carry a
  `PluginControllerActionContextInterface`, so a listener can tell which
  plugin asked, read the content element's settings and reach the request.
  That is the half of the `main` change that needs a signature change on
  `ProfileRepository::findByDemand()` and a second uid finder on both
  repositories, and it is breaking. This branch is a maintenance line; the
  events arrive without it.
- What follows from that: a listener here narrows **every** query of the
  plugins alike. It cannot act for one plugin only, and it cannot read a
  setting of the content element. A listener written against this branch runs
  unchanged on `main`, where it may then start reading the context.

## Capabilities

### New Capabilities

- `academic-persons/profile-query-constraints`: how an installed extension
  narrows the profiles and contracts the persons plugins show, on v12 and v13.

### Modified Capabilities

- `academic-persons/profile-list-pagination`: both requirements about a manual
  selection are narrowed to the profiles a listener leaves, exactly as on
  `main`. They were written before anything could exclude one.

## Impact

- `academic_persons`: two new event classes, the profile and contract
  repositories. **No call site or signature of the controller changes**; the
  one line it does get is a docblock reference to the renamed private method.
- A functional test fixture extension with two listeners, registered with the
  `event.listener` tag in `Services.yaml` - `TYPO3\CMS\Core\Attribute\AsEventListener`
  does not exist on TYPO3 v12.
- Integrator documentation and a `Feature-*.rst` for 2.4.0. No `Breaking-*.rst`.
- No schema, template or setting change.

## Non-goals

- Changing orderings through the event.
- The detail view, for which neither event is dispatched.
- Un-finalising the controller or opening the repository helpers.
- The plugin context, and with it any repository signature change.

## Source

Backport of ACE-715 on `main`, derived from a file level analysis rather than
a cherry-pick: see `design.md`.
