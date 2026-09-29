## Context

See `proposal.md` for the motivation. State on `main`:

- `ProfileController::initializeListAction()` allows the `settings.demand`
  keys plus `VISITOR_DEMAND_PROPERTIES` from the request: `currentPage`,
  `alphabetFilter` and, since `ace-735-list-view-modes`, `viewMode`. The same
  constant decides what the pagination and letter links carry
  (`ace-734-list-links-keep-state`).
- `ProfileDemand` carries the editor restriction as `functionTypes` and
  `organisationalUnits` (int lists), filled in `adoptSettings()` from
  `settings.functionTypes` and `settings.organisationalUnits`.
- `ProfileRepository::setFilters()` applies them as
  `in('contracts.functionType', ...)` and
  `in('contracts.organisationalUnit', ...)`.
- `FunctionTypeRepository::findAll()` and
  `OrganisationalUnitRepository::findAll()` ignore storage pages and order by
  `uid` only.
- The list, listanddetail and card plugins share `List.xml`, which exists as
  `Core13/List.xml` and `Core14/List.xml` (ACE-560). The card hides fields
  through `Configuration/TSconfig/Card/page.tsconfig`.
- Since this change was written, `ace-719-contract-display-policy` added
  `settings.contracts.matchFilter`: with it on, a profile shows only its
  contracts within the editor restriction
  (`ContractSelection::fromPluginSettings()`).
- Organisational units have a `parent`. The editor restriction matches the
  unit uid exactly, sub-units are not included.

All premises were re-checked on `main` at `8c59eee38` and hold.

## Goals / Non-Goals

**Goals:**

- A visitor can never widen what the editor restricted.
- Pagination and counts stay correct, because the filter is part of the
  query.

**Non-Goals:**

- Changing `findAll()` of either repository, which other callers use.

## Decisions

### Separate demand properties for visitor values

`ProfileDemand` gets `functionTypeFilter` and `organisationalUnitFilter`
(`int`, `0` = none).

Rejected: writing the visitor value into `functionTypes`. The editor
restriction lives there and would be overwritten, widening the list.

### Allow-list plus validation against the options

Both properties join `VISITOR_DEMAND_PROPERTIES`, so the property mapping
accepts them and the navigation links carry them. `initializeListAction()`
drops a value that `MathUtility::canBeInterpretedAsInteger()` rejects. The
integer type converter would otherwise fail the list on an array or a value
that is not numeric, and cut "1.5" or "1e3" down to a number the visitor did
not ask for.

After `adoptSettings()` has set the editor restriction on the demand,
`filterOptions()` loads the options of every filter whose FlexForm flag
(`settings.filter.functionType`, `settings.filter.organisationalUnit`) is on,
and `adoptVisitorFilters()` resets a value to `0` unless it is the uid of one
of them. A filter whose flag is off has no options, so this one check covers
the flag, unknown and hidden records, and values outside the restriction.

Rejected: relying on the property mapping alone, which accepts any integer.
Also rejected: a second flag check in `initializeListAction()`. It would
duplicate the check against the options, and no test could tell the two
apart.

### Same contract join for all contract constraints

The visitor constraint is ANDed as `equals('contracts.functionType', x)` next
to the editor's `in()`. Extbase reuses the join alias of a property path
within one query, so every constraint on `contracts.*` applies to the same
contract row. A test pins that, because it decides the "both filters"
requirement.

Rejected: independent `EXISTS` subqueries ("any contract has the type, any
contract has the unit"). That lists a person as "professor in unit X" who is
a professor elsewhere and staff in X.

### Decided: both filters match the same contract

When a visitor sets both filters, function type and organisational unit must
match one and the same contract of the profile.

Verified: Extbase reuses the join alias per property path
(`Typo3DbQueryParser::getUniqueAlias()`, `cms-extbase` v13 `:942-956`), so
both `contracts.*` constraints hit one contract row. Matching any contract
would list a professor elsewhere as "professor in unit X".

### Decided: single-value filters for now

The demand properties stay single `int` values. A multi-value visitor filter
(several function types at once) is not planned now and would be a change
of its own.

No analysed project filters by several values. The one production filter is
a single select. Multi-value relations are a data-model question that stays
out of upstream for now.

### Decided: the filter options are the restricted or all records

New repository methods, `findFilterOptions()` on the function type and
organisational unit repositories, return the options: the editor-restricted
records, or all records, ordered by `functionName` or `unitName` with a `uid`
tiebreaker. They are not narrowed to records used by a profile in the
list's storage folders.

The restriction of the content element stores default language uids, while
the query of a translated request matches `uid` against the translated rows,
in all three language modes. `findFilterOptions()` therefore runs the
language-aware query without a uid condition and keeps the restricted records
in PHP, by `getUid()`, which is the default language uid in every mode. The
tables are small, and the options come out translated and in the language
mode of the site. A `uid IN (...)` in the query returned no options at all in
a translation, pinned by a localized test. The options are ordered by their
translated names in all three modes, measured on v13 and v14.

Rejected: lifting the language handling as
`ProfileRepository::matchSelectedUidsAcrossLanguages()` does, which works but
orders by the default language names and sidesteps the strict mode.

The controller always assigns `filterOptions`, with a
key per filter the element offers, and an empty array for a manual selection.
An empty array and a missing variable are the same to a template, so an
assignment only when a flag is on would add a branch no test can observe.

This keeps parity with the editor restriction, which does not look at the
storage folders either, and it is what the one production filter does today.
Narrowing needs one more join through contracts and profiles per rendering,
and it can follow as a change of its own if sites report options that lead to
an empty list.

Rejected: only offering records that a contract of a profile in the storage
folders references.

### A manual selection offers no options

A manual selection ignores the filters in the query already, because the
`profileList` branch of `ProfileRepository::resolveDemandForQuery()` returns
before any filter. The controller also returns no options for it, so
`adoptVisitorFilters()` resets both values to `0` and no link carries a value
the list ignored.

### The filter does not narrow the shown contracts

`settings.contracts.matchFilter` keeps applying the editor restriction only.
The visitor filters select profiles, and the contracts shown for a profile
follow the contract options as before.

Rejected for now: passing the visitor values to `ContractSelection`. It reads
the plugin settings, not the demand, and a visitor filter narrowing the shown
contracts is a display choice of its own. It can follow as a change when a
site asks for it.

### The letter navigation follows the filter

`ProfileRepository::findAlphabetFilterLetters()` builds the list query through
the same `setFilters()`, so the letters that lead somewhere are the ones of
the filtered list, and the letter links keep the filter through the constant.
No code of its own, a test pins it.

### Card hides the flags

The card page TSconfig removes both new fields, as it already does for
`viewMode`.

Rejected: a listener on `ace-715-profile-query-constraint-event`. The event
could add the constraint, but the allow-list has to live in the controller,
and three projects want the same filter.

## Risks / Trade-offs

- [Duplicate rows through the contracts join] → The existing editor
  restriction already joins. A test with a profile carrying two matching
  contracts asserts it is listed once.
- [The options ignore storage pages, as `findAll()` does, so an option may
  lead to an empty result] → Accepted for parity with the editor
  restriction. The list renders its empty state, and narrowing the options can
  follow if sites report it.
- [Cache variants per filter value] → Bounded by the number of records and
  covered by the cHash.
- [Options stay cached] → The list cache tag `profile_list_view` is flushed
  on profile saves only, so a new, renamed or hidden function type or unit
  changes a cached list when its cache expires. The names shown in contracts
  behave the same today. The shipped templates render no options yet, so
  `ace-tbd-visitor-filter-ui-routes` flushes the tag for both tables when it
  adds the form.

## Open Questions

None.
