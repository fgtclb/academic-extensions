## Context

See `proposal.md` for the motivation. What exists on `main`:

- `skip_sync` on the profile only gates `academic:updateprofiles`
  (`academic-persons/Classes/Profile/AbstractProfileFactory.php`).
- The fe_users synchronisation writes `import_identifier` as `fe_users:<uid>`
  on profile, contract, address, e-mail and phone rows
  (`academic-persons/Classes/Profile/ProfileFactory.php`). The column is
  `passthrough` on all eight person tables, so it never reaches a form.
- The `readonly` validation flag becomes TCA `readOnly` for every record of the
  table through `TcaValidationMerger` in the TCA files. No FormEngine data
  provider exists in any academic extension.
- `tcaDatabaseRecord` in `DefaultConfiguration.php` is the data group on v13
  and v14 alike, and it compiles inline children too, so one provider covers
  the profile form and its nested contracts and contacts.

## Goals / Non-Goals

**Goals:**

- One place that answers "is field X of this row managed?", reused by the
  frontend editor and the import writer later.
- No change for an installation without a `managedFields` declaration.

**Non-Goals:**

- Enforcing the lock in DataHandler.
- Touching the existing `readonly` flag (see
  `ace-tbd-frontend-only-readonly-flag`).

## Decisions

### A `managedFields` key in the settings graph

```yaml
managedFields:
  profile: [title, firstName, lastName]
  contracts: [position, organisationalUnit, functionType]
  emailAddresses: [email, type]
  phoneNumbers: [phoneNumber, type]
  physicalAddresses: [street, zip, city]
```

The entries are the field identifiers the settings already use, resolved to
database columns through the existing field descriptors (`propertyName`,
`fieldName`), so an integrator writes the same names in `managedFields` as in
`profile`, `contracts.fields` and `contracts.contactSections`. An unknown
identifier fails the settings build with an exception naming it, like any
other invalid settings entry. The typed graph gets a small read-only value
object; `AcademicPersonsSettings` exposes it.

Rejected: a TCA `ctrl` key per table. Every project would need TCA overrides
for five tables, and the frontend editor could not read it without a second
source of truth.

### One stateless resolver, one data provider

`FGTCLB\AcademicPersons\Sync\ManagedFieldResolver` (`final readonly`) takes a
table name and a row and returns the managed column names. It is empty when
the row has no `import_identifier`, when it is not a default-language row, or
when its profile has `skip_sync = 1`. The owning profile of a contract or a
contact is read with the `QueryBuilder` (deleted restriction only, so a hidden
profile still counts, as the sync treats it).

`FGTCLB\AcademicPersons\Backend\Form\FormDataProvider\ManagedFieldsReadOnly`
is registered in `ext_localconf.php` for `tcaDatabaseRecord`, after
`TcaColumnsProcessShowitem` and before `TcaColumnsProcessFieldDescriptions`,
so the description it sets is still an `LLL:` reference that core translates.
It only touches `processedTca.columns` of the table being compiled. Services
are autowired through `Services.yaml`; the provider carries
`#[Autoconfigure(public: true)]` because FormEngine instantiates it through
`GeneralUtility::makeInstance()`.

Rejected: one project's approach of field lists in code, which also looped over
the lists of every table for every table.

### Presentation lock, not a write lock

The provider sets `config.readOnly` and appends the hint to `description`.
DataHandler is not changed: an admin correcting a synchronised value by
script, the fe_users sync and the future import writer must keep writing.

Rejected: a DataHandler hook refusing managed fields. It would also refuse the
synchronisation itself unless it learnt who is writing, which is state.

Guessed layout — a sketch, not a design:

```text
Position  [ Professor für ...        ] (read-only)
          (i) Maintained by synchronisation (fe_users:12)
Room      [ A 2.14                   ]
```

### Decided: translations stay editable

The lock covers default-language rows only. A managed field that is
translatable, such as contract `position` or profile `title`, stays editable
on translation records. It is revisited only if a project asks.

The synchronisation writes default-language rows only, and the resolver
returns nothing for other languages. `position`, `room` and profile `title`
have no `l10n_mode`, so their translated values are editorial content no
source delivers; locking them would make them untranslatable. The
`l10n_mode = exclude` columns (names, organisational unit, function type) are
already read-only on translations through core. Rejected: locking managed
translatable fields on translations too.

## Risks / Trade-offs

- [Provider order differs between v13 and v14] → Read both
  `DefaultConfiguration.php` files when registering it; the functional test
  compiles the form on both versions.
- [An extra query per contract or contact row in a large profile form] → One
  lookup per compiled record, bounded by the inline children of one profile.
- [A changed identifier list is only seen after a cache flush] → Same as every
  other settings change; documented.

## Open Questions

None.
