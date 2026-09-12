## Context

See `proposal.md` for the motivation. State on `main`:

- `ProfileController::initializeListAction()` allows the `settings.demand`
  keys plus `currentPage` and `alphabetFilter` from the request.
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

### Allow-list plus validation

`initializeListAction()` allows a property only when its FlexForm flag
(`settings.filter.functionType`, `settings.filter.organisationalUnit`) is on.
Through `ace-tbd-list-links-keep-state` the property joins the constant of
visitor-settable properties. `adoptSettings()` then resets the value to `0`
unless it is among the filter options. That handles unknown records and
values outside the restriction in one place.

Rejected: relying on the property mapping alone, which accepts any integer.

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

No analysed project filters by several values; the one production filter is
a single select. Multi-value relations are a data-model question that stays
out of upstream for now.

### Decided: the filter options are the restricted or all records

New repository methods return the options: the editor-restricted records, or
all records, ordered by `function_name` or `unit_name` with a `uid`
tiebreaker. They are not narrowed to records used by a profile in the
list's storage folders. The controller assigns them as `filterOptions` only
when a flag is on.

This keeps parity with the editor restriction, which does not look at the
storage folders either, and it is what the one production filter does today.
Narrowing needs one more join through contracts and profiles per rendering;
it can follow as a change of its own if sites report options that lead to
an empty list.

Rejected: only offering records that a contract of a profile in the storage
folders references.

### Card hides the flags

The card page TSconfig removes both new fields, as it already does for
`viewMode`.

Rejected: a listener on `ace-tbd-profile-query-constraint-event`. The event
could add the constraint, but the allow-list has to live in the controller,
and three projects want the same filter.

## Risks / Trade-offs

- [Duplicate rows through the contracts join] → The existing editor
  restriction already joins; a test with a profile carrying two matching
  contracts asserts it is listed once.
- [The options ignore storage pages, as `findAll()` does, so an option may
  lead to an empty result] → Accepted for parity with the editor
  restriction. The list renders its empty state; narrowing the options can
  follow if sites report it.
- [Cache variants per filter value] → Bounded by the number of records;
  covered by cHash.

## Open Questions

None.
