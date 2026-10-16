## Why

The list and list-and-detail elements of `academic_persons` drop a profile an
editor picked by hand in "List of profiles" when it is stored outside their
Startingpoint, without a notice, although the editor chose it explicitly. The
card and the selected profiles element, which pick profiles the same way,
never applied a storage folder (ACE-923, item 9).

This backports the `main` change archived there as
`2026-10-16-ace-923-picked-profiles-and-card-contracts` and keeps its name.
Its card part does not apply here: the card of this branch has no contract
restriction option and never reads its unit and function type fields, so
there is no stored restriction for it to ignore.

## What Changes

- The list and list-and-detail elements show every profile of "List of
  profiles" wherever it is stored, in the order of the selection. Their
  Startingpoint keeps deciding which profiles a list without a selection
  shows. Visibility, start and end time, deletion, "Show hidden records", the
  language handling and the workspace apply to a picked profile as before.

This applies to TYPO3 v12 and v13 alike.

## Capabilities

### New Capabilities

- `academic-persons/profile-manual-selection`: which profiles the list and
  list-and-detail elements show when an editor picks them by hand.

### Modified Capabilities

None.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons/`): the profile query
  for a selection by hand, the 2.4 `Important` changelog entry
  `Important-PickedProfilesIgnoreTheStartingpoint.rst` and a check in
  `Upgrade/From23To24.rst`, both byte-identical with `main`.
- A list that relied on its Startingpoint to drop picked profiles shows them
  now. Integrators who used that as a filter remove those profiles from the
  selection instead.
- No database, TCA or API change. Listeners of the profile query events see a
  query for a selection whose storage pages are still set but no longer
  respected.

## Non-goals

- Hiding the options that have no effect in an element (page title format,
  detail page, the card's units and function types) is a backend form change
  without specified behaviour and is done in its own commit of ACE-923.
- The contract display policy of the card, which exists on `main` only.
- Restricting the backend to profiles of the Startingpoint. FormEngine cannot
  limit a FlexForm group field to the pages of the same record.
- The selected profiles element, which already finds its profiles wherever
  they are stored.
