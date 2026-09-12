## Why

The frontend-user synchronisation maps a fixed set of `fe_users` columns onto
profiles and contracts: the names, title, website, one address, one e-mail
address, telephone and fax. Anything else, such as a mobile number, a
position or a room, needs a complete custom profile factory. Four projects
ship their own LDAP or fe_users factories. Every copy misses the upstream
fixes to sub-record matching and hidden handling.

## What Changes

- A new top-level `frontendUserSync` map in
  `Configuration/AcademicPersons/Settings.yaml` defines:
  - which `fe_users` column feeds which profile field;
  - the contract position and room;
  - lists of physical addresses, e-mail addresses and phone numbers, each
    phone with its type.
- The shipped defaults reproduce today's mapping exactly: the same fields,
  the same import identifiers, and the phone types from the extension
  configuration.
- A field mapped to `''` is not synchronised, and its value stays as editors
  set it.
- An empty source value removes the imported sub-record, as today.
- Custom profile factories can reuse the same mapping instead of copying it.

Only `academic_persons` (`packages/fgtclb/academic-persons`) is affected. The
behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/frontend-user-profile-sync`: adds requirements for the
  configurable field mapping and its defaults.

## Impact

- The default profile factory delegates its field writes to a new stateless
  mapper.
- `Settings.yaml` gains a key that is merged per package like the others.
  The whole map is replaced by the last package that ships it.
- No database change. Existing imported records keep their identifiers.
- Basis for `ace-tbd-fe-user-relation-mapping` and
  `ace-tbd-fe-user-sync-data-events`.

## Non-goals

- An upstream LDAP factory, or any dependency on an LDAP extension.
- Mapping relations (organisational unit, function type, employee type). That
  is `ace-tbd-fe-user-relation-mapping`.
- Multi-value sources, value transformations or visibility mapping.
- A backport to branch `2`, which lacks the 3.0 settings graph.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-04`). Four of the six analysed projects carry their own code for
this today: one an approximately 340-line factory and listener, three LDAP
factories that would keep only their attribute source. No YouTrack issue is
filed yet; the change is renamed to `ace-<NNN>-<slug>` when the issue is filed
after implementation.

Relates to ACE-278.
