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
- `SettingsFileLoader` merges per top-level key (`array_merge()`).

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
`AcademicPersonsSettings`. Unknown profile or contract property names throw
during normalisation, with an exception code, so a typo fails loudly.

Rejected: flat extension configuration keys, which cannot express lists.
Also rejected: an upstream LDAP factory, which would couple to
`causal/ig_ldap_sso_auth` while four projects use four different schemas.

### A stateless mapper applies it

A new `final readonly class FrontendUserProfileMapper` in `Classes/Profile/`
applies the mapping to a `Profile` and its first imported contract. It gets
the settings and the repositories injected and holds no data between calls.
`ProfileFactory` delegates to it on create and update. Custom factories
extending `AbstractProfileFactory` can inject it.

Rejected: making `ProfileFactory` non-final so projects can override single
`apply*` methods. That brings back the copies this change removes.

### Import identifiers stay compatible

The first entry of `physicalAddresses` and `emailAddresses` keeps
`fe_users:<uid>`. Further entries use `<column>:fe_users:<uid>`, naming the
first column of the entry. Phones keep `<column>:fe_users:<uid>`, including
the legacy telephone fallback. Existing records are therefore matched
without a migration.

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
- [A project ships a partial `frontendUserSync` map] → The top-level merge
  replaces the whole map. This is documented like the other keys.

## Open Questions

None.
