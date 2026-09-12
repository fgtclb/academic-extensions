## Why

Every view of `academic_persons` (`packages/fgtclb/academic-persons`) renders
every contract of a profile. Six projects reduce that list in Fluid instead:
to the first contract, to the first contract of the plugin's organisational
unit, or to contracts valid today. Filtering in the template gets "first"
wrong once a filter applies, and it has to be copied into every overridden
template. ACE-59 asked for the first contract on the detail page, and main
still does not offer it.

## What Changes

- Editors of the list, list-and-detail, card and selected-profiles plugins
  choose which contracts a profile shows:
  - all of them (the default, today's output), or only the first;
  - optionally only contracts of the plugin's organisational units and function
    types;
  - optionally only contracts valid today.
- Integrators choose all or first for the position and contact blocks of the
  detail view in `Settings.yaml` (default all), and optionally only contracts
  valid today (default off).
- Filters apply before "first": the first contract is the first of the
  remaining ones, in the editor's contract order.
- A contract without an end date stays valid; no setting changes that.
- While a validity option applies, a cached page expires at the next date on
  which a shown contract ends or a hidden one starts, so validity takes
  effect on that day and not only with the next regular cache expiry.
- The selected-contracts plugin and `academic_contacts4pages` keep showing the
  contract that was chosen.

The behaviour is identical on TYPO3 v13 and v14. The new FlexForm fields go
into both `Core13/List.xml` and `Core14/List.xml`.

## Capabilities

### New Capabilities

- `academic-persons/contract-display-policy`: which of a profile's contracts
  the profile views show.

### Modified Capabilities

None.

## Impact

- `academic_persons`: a new stateless contract selection service, a new
  ViewHelper namespace (the extension ships no ViewHelper yet), the contract
  item partial and the position and contact partials of the detail view, the
  List and SelectedProfiles FlexForms, `Settings.yaml` and its normaliser,
  labels. The ViewHelper restricts the page cache lifetime through the core
  cache data collector of the request.
- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`): no
  change; verified by a test that its output stays the same.
- No schema change.

## Non-goals

- Honouring the contract publish flag (its own change, which builds on this
  one).
- Hiding a profile that has no contract left after filtering.
- Filtering in SQL.
- The backend contract selectors (ACE-51 is only partly covered: old contracts
  can be hidden in the frontend, not in the selectors).

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-06`). All six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-contract-display-policy` when the issue is filed after
implementation.

Relates to ACE-59. Relates to ACE-51.
