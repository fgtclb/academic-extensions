## Context

See `proposal.md` for the motivation. State on `main`:

- `ProfileRepository::setFilters()` puts every condition on contracts on one
  joined contract: the restriction of the element (`demand.functionTypes`,
  `demand.organisationalUnits`) with `in()`, the visitor filters
  (`demand.functionTypeFilter`, `demand.organisationalUnitFilter`) with
  `equals()`. Extbase joins `contracts` once per query and reuses the alias, so
  all of them apply to the same contract. The list, its pagination count and
  `findAlphabetFilterLetters()` build their query through this method.
- "Only contracts valid today" (`settings.contracts.onlyValid`) is applied by
  `ContractSelector` while rendering. Its day rule: a contract is valid when the
  day of its start, in the timezone of the `date` aspect of the `Context`, is
  not after today, and the day of its end is not before today. A missing date
  is open-ended. `valid_from` and `valid_to` are nullable `int` timestamps with
  `format => date`.
- `ContractsViewHelper` caps the page cache lifetime through
  `CacheDataCollector::restrictMaximumLifetime()` with the next boundary of the
  contracts it rendered.

## Goals / Non-Goals

**Goals:**

- The query selects a profile only through a contract the list would show, with
  the same day rule as the selector.
- The page cache expires on the day a contract matching the list's conditions
  starts or ends, whether or not its profile is listed today.

**Non-Goals:**

- Any change to the elements that select no profile through a contract.

## Decisions

### The demand says whether only valid contracts count

`ProfileDemand` gets a boolean property, written by
`ProfileController::adoptSettings()` from `settings.contracts.onlyValid`, and
read by `setFilters()`. It is not one of the visitor demand properties, so the
request cannot set it, and `settings.demand` of the element wins over the
request as for every other key.

Rejected: reading the settings from the plugin context inside the repository.
The context is optional (`findByDemand()` without a plugin passes none), and
the demand is what the query is built from everywhere else. Also rejected: a
`ModifyProfileQueryEvent` listener of the extension itself, which would put
core behaviour of the list behind the extension point projects use.

### Validity joins the contract the other conditions are on

Only when `setFilters()` adds at least one condition on contracts, it adds two
more on the same alias:

- `contracts.validFrom` is `NULL` or before the start of tomorrow, which an
  empty start of `0` always is,
- `contracts.validTo` is `NULL`, `0` or not before the start of today,

with today and tomorrow as midnight in the timezone of the `date` aspect. A
timestamp's day is not after today exactly when the timestamp is before the
start of tomorrow, and its day is not before today exactly when it is not
before the start of today, so the query and `ContractSelector` agree on every
contract. The same alias is what keeps "a professor in unit X with a valid
contract" one contract, as for the two visitor filters (ACE-779).

Without a condition on contracts nothing is added: the list would otherwise
drop every profile whose contracts have all ended or not started yet, which
ACE-719 ruled out.

Rejected: filtering the profiles in PHP after the query, which breaks the
pagination count and the letter navigation, the reason ACE-719 rejected
filtering in Fluid.

### The list caps the cache lifetime with the next boundary of matching contracts

`ProfileRepository` gets a method that returns the next day on which a
contract meeting the demand's conditions on contracts starts or ends: the
smallest `valid_from` from the start of tomorrow on, and the day after the
smallest `valid_to` from the start of today on, among the contracts that carry
the demanded function types and units, of the profiles on the storage pages of
the list. It reads the demand after `ModifyProfileDemandEvent`, dispatched on a
copy as for the letters, so a listener that adds or removes a condition
changes the answer as the list. It answers `null` while the demand has no
condition on contracts, selects its profiles by hand, or does not count only
valid contracts. `listAction()` restricts the page cache lifetime to that day
through the request's cache data collector, as `ContractsViewHelper` does.

ACE-719 rejected "a page cache listener that queries every contract boundary of
the storage folders", because it shortens the lifetime of pages by contracts
they never render. That rejection was about the contracts a page shows, which
the rendering knows. Here the list itself depends on contracts it does not
render: without the query a professor appointed tomorrow stays missing until
the regular expiry, a year on TYPO3 v14. The query is bound to what the list
query reads, the conditions and the storage pages, so it only reaches contracts
that could change this list. What it still does not know is the language of
the profiles: a boundary of a contract the list never shows makes the lifetime
shorter, never too long. A hidden contract, which a list that shows hidden
records selects by, is left out by the default restrictions, and its page keeps
the regular expiry.

Rejected: the boundaries of the listed profiles only. A contract that starts
tomorrow belongs to a profile that is not listed today, so the rendering never
sees it. Also rejected: `ModifyCacheLifetimeForPageEvent`, for the reason
ACE-719 gave: the listener would need per-request state in a shared service.

### What this changes of ACE-719

ACE-719 named "hiding a profile that has no contract left after filtering" as a
non-goal. A list without conditions on contracts keeps that. A list with such
conditions now leaves out a profile it would only find by an invalid contract,
and with "only contracts matching the plugin filter" on as well, every profile
it still lists shows a contract. For those lists the former non-goal is what
this change delivers, on purpose: the profile was listed because of the
contract the list then hid.

## Risks / Trade-offs

- [The lifetime is shortened by contracts of profiles the list leaves out for
  their language or visibility] → Only while the option and a condition on
  contracts are both active, and never longer than needed.
- [Two queries and one more dispatch of `ModifyProfileDemandEvent` per list
  that shows only valid contracts] → The boundary query reads the contracts of
  the storage pages with two aggregates. A list without the option or with a
  manual selection does neither.
- [An installation counting on the old matching] → Both options are new in
  3.0, nothing released depends on it. The 3.0 entries of both features
  describe the combined behaviour.

## Migration Plan

None. No schema change, no new setting.
