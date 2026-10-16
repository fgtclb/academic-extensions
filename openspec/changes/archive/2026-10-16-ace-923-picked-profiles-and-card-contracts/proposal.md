## Why

Three elements of `academic_persons` show profiles an editor picks by hand, and
two of them ignore what a list would apply on top of the pick. The list and
list-and-detail elements do not: a picked profile stored outside their
Startingpoint is dropped without a notice, although the editor chose it
explicitly. The card, in turn, still narrows its contracts by a unit and
function type restriction stored while it was a list, which the contract
display policy says it neither offers nor applies (ACE-923, items 3 and 9).

## What Changes

- The list and list-and-detail elements show every profile of "List of
  profiles" wherever it is stored, in the order of the selection. Their
  Startingpoint keeps deciding which profiles a list without a selection
  shows. Visibility, start and end time, deletion, "Show hidden records", the
  language handling and the workspace apply to a picked profile as before.
- The card ignores "Only contracts of the selected organisational units and
  function types" stored while the element was a list, and shows the
  contracts its other options leave. The backend form of the card no longer
  offers the two restriction fields either, which is part of ACE-923 but not
  of this change, see Non-goals.

Both apply to TYPO3 v13 and v14 alike.

## Capabilities

### New Capabilities

- `academic-persons/profile-manual-selection`: which profiles the list and
  list-and-detail elements show when an editor picks them by hand.

### Modified Capabilities

- `academic-persons/contract-display-policy`: the requirement on the option
  that limits contracts to the plugin filter states that the card applies no
  stored value of it.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons/`): the profile query
  for a selection by hand, the card action, the integrator manuals, a 2.4
  `Important` changelog entry for the Startingpoint (the defect exists on
  branch `2` as well) and the 3.0 entry of the contract display policy.
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
- Restricting the backend to profiles of the Startingpoint. FormEngine cannot
  limit a FlexForm group field to the pages of the same record.
- The selected-profiles element, which already finds its profiles wherever
  they are stored.
