## Why

A listener of the demand event of the persons list of `academic_persons`
(`packages/fgtclb/academic-persons`) that changes the demand it receives in
place changes more than the query: the list keeps using the same object, so
the order of a selection by hand, the page, the letter that switches the
pagination off and the demand the list event and the template receive follow
the listener (ACE-920). Branch `2` has the same dispatch and list action as
`main`, on TYPO3 v12 and v13.

This backports the change `ace-920-demand-event-on-clone` of `main`, archived
there as `openspec/changes/archive/2026-10-16-ace-920-demand-event-on-clone/`,
re-derived from the backport analysis of this branch.

## What Changes

- The list query hands the listeners a copy of the demand. A listener changes
  the profiles the list queries, whether it changes the copy in place or hands
  back another demand, and never the demand of the list.
- Maintainer decision, "the request decides", the same as on `main`: the order
  of a selection by hand, the pagination, the letter that switches the
  pagination off and the demand the list event and the template receive
  follow the demand of the request, whatever a demand listener does. The
  listener's change decides the query only. A listener that relied on the
  leak, to switch the order of a selection on or off for example, loses that
  effect.
- The listeners of the list event, dispatched after the query, receive the
  demand of the list, never one a demand listener changed in place. They can
  still replace the demand of the list, as before.
- The card plugin, the only other caller, reads nothing back from its demand.
- The docblock of the event closes its reference and says that the listeners
  receive a copy.
- The `Changelog/2.4/Important-*.rst` entry and the upgrade guide from 2.3 to
  2.4 are taken from `main` byte for byte, since both are shared between the
  branches.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/profile-query-constraints`: a requirement is added that a
  listener changing the demand of the list query changes the profiles queried
  and nothing else of the list.

## Impact

- `academic_persons` only: the profile repository, the docblock of the demand
  event, a listener and a functional test with its fixture in the test
  extension `test_profile_query_constraints`, a unit test of the demand copy,
  the changelog, the upgrade guide from 2.3 and the page on fixture extensions
  in `docs/`.
- TYPO3 v12 and v13 behave the same.
- No database, TCA, FlexForm, template or dependency changes. The event keeps
  its signature.

## Non-goals

- Letting the controller ask the repository for the effective demand, a
  non-goal on `main` as well.
- The letters and the next contract validity change, which ask the listeners
  again on `main`. This branch dispatches the event once per rendering.
- The developer manual page of the event, the upgrade guide to 3.0, the 3.0
  changelog and `docs/architecture/list-plugin-events.md` that `main` changed.
  None of them exists on this branch.
