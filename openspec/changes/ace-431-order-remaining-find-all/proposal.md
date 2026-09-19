## Why

ACE-431 listed the repository `findAll()` methods of `academic_persons` that
carry no ordering. ACE-482 and ACE-491 ordered most of them; four are left:
the address, e-mail address, phone number and profile information
repositories. Their result order is whatever the database returns, which
PostgreSQL does not keep stable once an index gives its planner an
alternative. They are the last query paths of the extension that break rule
3 of `docs/architecture/database-queries.md`.

## What Changes

- `academic_persons` (`packages/fgtclb/academic-persons`): the `findAll()`
  of `AddressRepository`, `EmailRepository`, `PhoneNumberRepository` and
  `ProfileInformationRepository` order by `uid` ascending. Their tables are
  manually sortable, but their `sorting` is scoped per parent contract or
  profile through an inline relation, so it means nothing across parents —
  the case rule 3 names for `uid`.
- Their functional tests compare the uids in result order instead of as a
  sorted set.
- An `Important-` changelog entry.
- TYPO3 v13 and v14 alike; nothing differs between them.

## Capabilities

### New Capabilities

None. No plugin, backend form or command of the repository calls these four
methods; they are public PHP API. The order they return is not observable by
a visitor, an editor or an integrator configuring anything, so the change
sets `skip_specs: true`.

### Modified Capabilities

None.

## Impact

Four repository methods and four test classes in `academic_persons`, one
changelog entry. No schema, TCA, template or configuration change. Code calling
these methods gets a stable order, which is the order SQLite, MySQL and MariaDB
return in practice; PostgreSQL promises none.

## Non-goals

- Ordering by `sorting` across parents.
- `ContractRepository::getContractItemsForTcaItemsProcFunc()` ignoring its
  parameters and the storage page lift of these methods, which ACE-431 names
  as related: that is a decision about which records a select offers, not
  about their order.
- The contacts of `academic_contacts4pages`, which order by `sorting` without
  a `uid` tiebreaker.
