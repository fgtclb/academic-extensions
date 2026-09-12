## Why

A project that needs one more condition on the profiles `academic_persons`
(`packages/fgtclb/academic-persons`) shows has no way to add it. The profile
controller is `final`, every helper that builds the repository query is
`private`, the uid lookups dispatch no event, and the existing demand event
cannot carry a condition the repository does not know. Two projects copied
the controller and the repositories (and one of them has since fallen back to
filtering after the query, which breaks pagination and counts). The copies
miss every fix main made since: deterministic ordering, hidden records, the
language handling of selections.

## What Changes

- New PSR-14 events let a listener add query conditions to the profile query
  of the list, list-and-detail and card plugins, to the profile uid lookup of
  the selected-profiles plugin, and to the contract uid lookup of the
  selected-contracts plugin.
- The added conditions are combined with the plugin's own ones and applied
  before pagination, so page counts reflect them. Orderings stay with the
  extension.
- Listeners learn which plugin, settings and request triggered the query,
  through the shared plugin context type of `academic_base`.
- **BREAKING** The profile demand finder gains an optional trailing context
  parameter. A subclass or XCLASS that overrides it must add it. Callers are
  unaffected.
- The two uid lookups keep their signatures. The plugins call new
  context-aware uid finders next to them, so an override of a uid lookup keeps
  loading.
- Other extensions add their conditions through these events instead of new
  finder parameters; the consent filter of
  `ace-tbd-public-display-consent` is the first.
- A context-less form of the events is backported to branch `2` as a separate
  change and released with 2.4.0.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/profile-query-constraints`: how an installed extension
  narrows the profiles and contracts the persons plugins show.

### Modified Capabilities

None.

## Impact

- `academic_persons`: two new event classes, the profile and contract
  repositories, the calls in the profile controller.
- `academic_base` (`packages/fgtclb/academic-base`): its plugin context
  interface becomes the type of the event context; nothing changes there.
- A functional test fixture extension with a listener.
- Integrator documentation, a `Feature-*.rst` and a `Breaking-*.rst`.
- No schema, template or setting change.

## Non-goals

- Changing orderings through the event.
- The detail view, which resolves its profile through argument mapping rather
  than a repository finder.
- Un-finalising the controller or opening the repository helpers.
- Profile queries of `academic_persons_edit` and of the synchronisation
  commands.
- A signature change of the two uid lookups.
- The branch `2` backport itself, which is a change of its own there.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-05`). Two of the six analysed projects carry their own code
for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-profile-query-constraint-event` when the issue is filed after
implementation.
