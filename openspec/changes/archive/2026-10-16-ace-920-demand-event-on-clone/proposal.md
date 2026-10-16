## Why

A listener of the demand event of the persons list that changes the demand it
receives in place, instead of handing back a copy, changes more than the
query: the list keeps using the same object afterwards, so the order of a
selection by hand, the page the pagination shows, the demand the template
receives and the two further questions the list asks about its demand follow
the listener (ACE-920). The result depends on how a listener is written, and
the manual has to warn against the most obvious way to write one.

## What Changes

- The list query hands the listeners a copy of the demand, as the questions
  for the letters and for the next contract validity change already do. A
  listener changes the profiles the list queries, whether it changes the copy
  in place or hands back another demand, and never the demand of the list.
- Maintainer decision, "the request decides": the order of a selection by
  hand, the pagination, the active letter and the demand the template
  receives follow the demand of the request, whatever a listener does. The
  offered letters and the next contract validity change are asked for with
  the demand of the request, which the listeners change again for each
  question on a copy of their own. A listener that relied on the leak, to
  switch the order of a selection on or off for example, loses that effect.
- The card plugin and the other callers pass demands of their own and read
  nothing back from them, so they render as before.
- The docblock of the event names all three places it is dispatched in.
- The developer manual page of the event drops the pitfall "Hand back a copy"
  and states the promise for every listener. An `Important` entry in
  `Changelog/2.4/`, written to hold on branch `2` as well, describes the
  change.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/profile-query-constraints`: a requirement is added that a
  listener changing the demand of the list changes the profiles queried, the
  letters offered and the next contract validity change, each on a copy of
  the request demand, and nothing the list reads from its demand otherwise.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`) only: the profile
  repository, the docblock of the demand event, a listener and a functional
  test with its fixture in the test extension
  `test_profile_query_constraints`, a unit test of the demand copy, the
  developer manual page of the event and the changelog.
- TYPO3 v13 and v14 behave the same.
- No database, TCA, FlexForm, template or dependency changes. The event keeps
  its signature.

## Non-goals

- Letting the controller ask the repository for the effective demand, which
  would make a selection a listener sets ordered by hand. The maintainer
  decided that the request decides.
- The demand events of `academic_partners`, `academic_programs` and
  `academic_projects`. They are dispatched by the controllers, which hand the
  replaced demand to the view on purpose, and are not part of ACE-920.
- Branch `2`, which gets its own change from the backport analysis.
