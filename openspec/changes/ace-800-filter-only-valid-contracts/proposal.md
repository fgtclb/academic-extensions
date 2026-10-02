## Why

A persons list that shows only contracts valid today (ACE-719) still selects
its profiles through every contract. The restriction of the content element and
the visitor filters of ACE-779 match a profile through a contract that has
ended or not started yet, and the list then shows that profile without the
contract it was found by. The development instances made it visible with
ACE-799: a filter by "Research assistant" lists a person whose only contract
of that kind ended last year, with nothing under the name.

## What Changes

- While a list or list-and-detail element shows only contracts valid today, the
  conditions on contracts in its query, the restriction to function types and
  organisational units and both visitor filters, match only contracts valid
  today. A profile is listed because of a contract only when the list can show
  that contract.
- The pagination and the letter navigation follow, because they count the same
  query.
- The page cache lifetime of such a list ends at the next day on which a
  contract that matches its conditions starts or ends, so a profile appears and
  disappears on the day its contract changes, also when nothing of it is shown
  yet.
- A list without conditions on contracts is unchanged. A profile whose
  contracts have all ended stays listed without a contract, as ACE-719 decided.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/contract-display-policy`: the option "only contracts valid
  today" also decides which contracts select a profile while the list has
  conditions on contracts, and the page cache follows the boundaries of those
  contracts.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`): the list query of
  `ProfileRepository`, the profile demand, which carries whether only valid
  contracts count, the list action of `ProfileController`, which caps the page
  cache lifetime, the configuration and developer documentation, and the two 3.0
  changelog entries of the features it combines.
- No database change, no new setting. Installations that never switched the
  option on, or have no filter or restriction, see no difference.

## Non-goals

- Hiding a profile that has no contract left to show from a list without
  conditions on contracts. ACE-719 named that as a non-goal and it stays one.
  A list with such conditions now leaves out a profile it would only find by an
  invalid contract. Combined with "only contracts matching the plugin filter",
  every profile it still lists shows a contract, so for those lists this change
  does deliver what ACE-719 left out, deliberately.
- Applying the visitor filters to the contracts that are shown. They select
  profiles (ACE-779), the option "only contracts matching the plugin filter"
  is about the restriction of the element.
- The detail, card, selected profiles and selected contracts elements, which
  select no profile through a contract.
- A backport to branch `2`, which has neither the option nor the filters.
