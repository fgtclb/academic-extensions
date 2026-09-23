## Context

- `ProfileFactory` (`academic-persons/Classes/Profile/ProfileFactory.php`) is
  `final` and shared (`#[Autoconfigure(public: true, shared: true)]`, `:25`).
  It hard-codes the profile fields (`:139-148`) and the contract contacts
  (`:154-174` and the `apply*` helpers).
- Its import identifiers are `fe_users:<uid>` for the profile, contract,
  address and e-mail. Phones use `<column>:fe_users:<uid>` with the legacy
  `phone:` fallback for telephone (ACE-365).
- Phone types come from `FrontendUserPhoneNumberTypeResolver`, which reads
  the extension configuration.
- `AcademicPersonsSettingsFactory` normalises `Settings.yaml` into a cached
  object graph (`__set_state()` on every object). It reads only the keys it
  knows, so an unknown top-level key is ignored today.
- `SettingsFileLoader` merges the package files recursively
  (`ace-711-settings-loader-deep-merge`).

## Goals / Non-Goals

**Goals:**

- A mapping that custom factories can call, so an LDAP factory only supplies
  data.
- Defaults that reproduce today's database writes byte for byte.

**Non-Goals:**

- Validating that a mapped column exists in `fe_users`. Listeners of
  `ace-tbd-fe-user-sync-data-events` may add keys that are not columns.

## Decisions

### The mapping lives in Settings.yaml as `frontendUserSync`

```yaml
frontendUserSync:
  profile:
    title: title
    firstName: first_name
    middleName: middle_name
    lastName: last_name
    website: www
  contract:
    position: ''
    room: ''
  physicalAddresses:
    - { street: address, zip: zip, city: city, country: country }
  emailAddresses:
    - { column: email }
  phoneNumbers:
    - { column: telephone, type: '' }
    - { column: fax, type: '' }
```

It is normalised into a `FrontendUserSyncSettings` sub-graph of
`AcademicPersonsSettings`, whose list entries are `FrontendUserSyncEntry`
objects (the columns per record property, and a phone number's type). A
property mapped to `''` is left out of the graph.

The supported properties are fixed lists: the profile's thirteen free text
and link fields (the names, title, website and its title, the publications
link and its title, and the five text fields), the contract's `position` and
`room`, and the seven address fields. The gender is a fixed selection and the
`*Alpha` letters are derived, so neither can be mapped. A phone number's
empty `type` takes `faxNumberType` for the column `fax` and
`telephoneNumberType` for every other column.

Rejected: flat extension configuration keys, which cannot express lists.
Also rejected: an upstream LDAP factory, which would couple to
`causal/ig_ldap_sso_auth` while four projects use four different schemas.

### A mistake is thrown where the map is used, not where it is read

Updated during implementation, decided by the maintainer. The draft had the
normaliser throw on an unknown property. That graph is also built while the
TCA is loaded - all six person TCA files read it - and no other key of it
throws, so a typo in the synchronisation map would stop every backend,
frontend and CLI request. The normaliser records each mistake instead - an
unknown key or property, a value that is not a string, a list that is not a
list, an entry that maps no column, two entries of one list sharing their
first column, a phone entry reading the column `phone`, whose identifier is the
one of the telephone records before ACE-365 - in `problems`, and
the mapper calls `FrontendUserSyncSettings::assertValid()` before it writes
anything: `\UnexpectedValueException` 1790142324, naming every mistake with
its path.

A graph cached before this change has no `frontendUserSync`;
`AcademicPersonsSettings::__set_state()` turns the missing key into a map with
a problem ("flush the TYPO3 caches") rather than an empty one. The cache
identifier stays `AcademicPersons_Settings_v3`, which only ever existed on
`3.0.0-dev`.

### A column the frontend user record lacks is refused by the default factory

The map is not validated against the `fe_users` schema (a non-goal above), so
a misspelled column would read as empty and remove the imported records. The
default factory synchronises full `fe_users.*` rows, so it calls
`FrontendUserProfileMapper::assertColumnsExist()` first, which refuses a
mapped column - and a missing `uid` - the row does not have
(`\UnexpectedValueException` 1790142326). A custom factory with sparse data
skips the call and gets the tolerant behaviour.

### A map without contract sources leaves the contract alone

"No mapped source is set" removes the imported contract; "no source is mapped"
must not, or `frontendUserSync: ~` and a names-only site package would remove
every imported contract - with the records editors added, because the contract
cascades its removal to its contact records.
`FrontendUserSyncSettings::mapsContract()` tells the two apart, and without a
source `ProfileFactory` neither creates, nor writes, nor removes the
contract.

### A stateless mapper applies it

A new `final readonly class FrontendUserProfileMapper` in `Classes/Profile/`
applies the mapping to a `Profile` and its first imported contract. It gets
the settings and the repositories injected and holds no data between calls.
`ProfileFactory` delegates to it on create and update. Custom factories
extending `AbstractProfileFactory` can inject it.

It is handed the shared `AcademicPersonsSettings` graph, not its factory, whose
`get()` evaluates the cached graph again on every call. Its public methods are
`assertColumnsExist()`, `applyProfile()`, `mapsContract()`,
`hasContractData()` and `applyContract()`. The factory keeps the records: the
profile, the import identifier of the profile, and creating or removing the
imported contract. `academic:createprofiles` still creates that contract with
every new profile, data or not, as before - unless the map names no source of
it, see above.

Rejected: making `ProfileFactory` non-final so projects can override single
`apply*` methods. That brings back the copies this change removes.

### Import identifiers stay compatible

The first entry of `physicalAddresses` and `emailAddresses` keeps
`fe_users:<uid>`. Further entries use `<column>:fe_users:<uid>`, naming the
first column of the entry. Phones keep `<column>:fe_users:<uid>`, including
the legacy telephone fallback. Existing records are therefore matched
without a migration.

The identifier follows the position and the first column, so an entry moved to
the front writes onto `fe_users:<uid>`, and the record it wrote before is no
longer synchronised. Rejected: identifying every entry by its column and
keeping `fe_users:<uid>` for the shipped columns only, which is independent of
the order but imports a new record when a project changes the column of its
first e-mail address, the more common edit.

### The contract guard follows the mapping

"The frontend user has contract data" becomes "any mapped contract source is
non-empty". This replaces the hard-coded column list.

### Decided: `frontendUserSync` in Settings.yaml, no separate file

The mapping lives in `Configuration/AcademicPersons/Settings.yaml` under the
top-level key `frontendUserSync`. The settings graph is not editor-only
today: `academic_persons` itself reads it in five classes and in all six
person TCA files, and `ace-tbd-managed-fields-backend` puts `managedFields`
into the same graph. One file keeps one loader, one cache and one patch
mechanism (`ace-tbd-settings-per-field-merge`) for all these keys.

## Risks / Trade-offs

- [The default drifts from today's writes] → The existing
  `ProfileCreateCommandService/UsingDefaultProfileFactoryOnlyTest` and
  `ProfileUpdateCommandService/UsingDefaultProfileFactoryOnlyTest` must stay
  green without a fixture change.
- [A project ships a partial `frontendUserSync` map] → The recursive merge of
  the loader applies the keys it names and keeps the rest, exactly as for the
  other keys, and its lists are replaced as a whole. This is documented like
  the other keys.

## Open Questions

None.
