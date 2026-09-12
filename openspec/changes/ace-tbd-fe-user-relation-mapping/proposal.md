## Why

The frontend-user synchronisation never sets a contract's organisational
unit, function type or employee type. Projects that take these from LDAP or
HR data write complete custom factories. Four projects ship their own LDAP
or fe_users factories. One of them creates missing organisational units on
page 1, because no storage page can be configured.

## What Changes

- The `frontendUserSync.contract` map gains two relation entries:
  - organisational unit, matched by unique name or unit name;
  - function type, matched by function name.
- For both, an integrator can allow creating a missing record on a
  configured storage page. Creation is off by default.
- A match that finds several records takes the lowest uid, so the result is
  the same on every database.
- An empty source value clears the relation. An unmapped relation is not
  touched.
- The employee type is not mapped. The documentation shows how a project
  sets it in a listener of the mapped-profile event that
  `ace-tbd-fe-user-sync-data-events` adds.

Only `academic_persons` (`packages/fgtclb/academic-persons`) is affected. The
behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/frontend-user-profile-sync`: adds requirements for
  mapping contract relations from frontend-user data.

## Impact

- The frontend-user mapper of `ace-tbd-settings-driven-fe-user-mapping`,
  which this change depends on.
- New read queries on organisational units and function types. New records
  only where creation is configured.
- The employee-type listener recipe in the documentation refers to the event
  of `ace-tbd-fe-user-sync-data-events`.
- No database schema change, and no dependency on `category_types`.

## Non-goals

- An employee type mapping, with or without a category type restriction.
  `academic_persons` does not require `category_types`, and the employee
  type relation has no type restriction, so a title match would be
  ambiguous. A listener covers the one project that needs a typed category.
- Several organisational units or function types per contract. The model
  holds single relations.
- Creating system categories.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-05`). Three of the six analysed projects carry their own code for
this today: an organisational unit lookup and creation, multi-value relations
(only partly covered) and an employee-type category lookup, which stays
project code in a listener. No YouTrack issue is filed yet; the change is
renamed to `ace-<NNN>-<slug>` when the issue is filed after implementation.

Relates to ACE-278.
