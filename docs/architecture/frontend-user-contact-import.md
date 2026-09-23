# Frontend-user contact import

`academic_persons` creates and updates profiles from `fe_users` through the
`academic:createprofiles` and `academic:updateprofiles` commands. Which column
feeds which property is configured, not coded: the `frontendUserSync` map of
`Configuration/AcademicPersons/Settings.yaml` (see below). Its shipped value
maps telephone and fax to phone-number records on the imported profile
contract, next to one physical address and one email address.

## The mapping is a settings key

`frontendUserSync` is the fifth top-level map of the persons settings graph
(`AcademicPersonsSettingsFactory::normalizeFrontendUserSync()` builds
`FrontendUserSyncSettings`). `profile` and `contract` map single properties to
columns; `physicalAddresses`, `emailAddresses` and `phoneNumbers` are lists of
entries, one record each. A property mapped to `''` or `~` is dropped by the
normaliser and therefore never written; `~` needs that rule inside a list
entry, because the loader replaces a list as a whole and keeps its `null`s.

The shipped map reproduces the hard-coded mapping it replaced record for
record. The proof is that both `UsingDefaultProfileFactoryOnlyTest` classes
stayed green without a fixture change; `FrontendUserSyncMappingTest` covers a
site package's map through the fixture extension `test_frontend_user_sync`.

The writes happen in `Profile\FrontendUserProfileMapper`, a stateless
`final readonly` service that `ProfileFactory` delegates to and a custom
factory can inject. It is handed the shared `AcademicPersonsSettings` graph
rather than its factory: the factory's `get()` evaluates the cached graph again
on every call, and the mapper is asked four or five times per profile. The
factory keeps the records: it creates the profile and the imported contract,
and removes that contract when `FrontendUserSyncSettings::hasContractData()`
finds no mapped source set.

**"No source is set" and "no source is mapped" are different.** A map without
any contract source (`mapsContract()` is false) - `frontendUserSync: ~`, or a
site package that keeps only the names - would otherwise read as "every source
is empty" for every user, and an update would remove every imported contract.
The contract carries `ACADEMIC_PERSONS_CASCADE_REMOVE` on its three contact
storages, so the records editors added to it would go with it. Such a map
therefore leaves the contract alone.

**A mistake in the map does not throw where the graph is built.** The graph is
also built while the TCA is loaded, so an exception there would stop every
request of the installation over a typo in the synchronisation. The normaliser
records the mistake in `problems` instead, and the mapper calls
`assertValid()` first - `\UnexpectedValueException` 1790142324, naming every
mistake with its path - before it writes anything.

The map is not checked against the `fe_users` schema; the design leaves that
out on purpose, because listeners may add keys that are no column. What
`ProfileFactory` checks is the row it was handed: `assertColumnsExist()`
refuses a mapped column the row does not have (1790142326). The provider
selects `fe_users.*`, so only a typo reaches it, and a misspelled column no
longer reads as empty and removes the imported records. A custom factory with
sparse data - an LDAP entry omits empty attributes - skips the call.

A graph cached before the map existed has no `frontendUserSync`.
`AcademicPersonsSettings::__set_state()` turns that into a map with a problem
("flush the TYPO3 caches"), so a stale entry refuses the synchronisation
instead of passing for an empty map. The cache identifier stays
`AcademicPersons_Settings_v3`: it only ever existed on `3.0.0-dev`.

## Visibility does not stop the synchronisation

Both commands ignore the visibility of a record when they select it, and
exclude only deleted records:

| Record     | Ignored when selecting                       | Still excluded |
|------------|----------------------------------------------|----------------|
| `fe_users` | `disable`, `starttime`, `endtime`            | `deleted`      |
| profile    | `hidden`, `starttime`, `endtime`, `fe_group` | `deleted`      |

Visibility says when and to whom a record is shown, not whether the person
behind it exists, and a command-line run has no frontend user group that a
restricted profile could match. ACE-242 lifted the hidden flags; ACE-667 the
start and end times and the frontend user group.

The profile fields are not a fixed list. The lookup asks the TCA schema of the
profile table which of the four it declares as enable columns, so an
installation that removes one is not handed a field its table does not have,
and an enable column added later stays in effect.

Visibility is never written: a synchronised profile keeps all four values, and
a profile outside its window stays invisible in the frontend. The display paths
— the "show hidden records" option, the selected profiles and the detail view —
share a different helper, which lifts the hidden flag only.

## Identity and presentation are separate

The source field defines the import identity:

| Source field           | Import identifier               | Configured type option                              |
|------------------------|---------------------------------|-----------------------------------------------------|
| `telephone`            | `telephone:fe_users:<uid>`      | the entry's `type`, else `telephoneNumberType`      |
| `fax`                  | `fax:fe_users:<uid>`            | the entry's `type`, else `faxNumberType`            |
| any other phone column | `<column>:fe_users:<uid>`       | the entry's `type`, else `telephoneNumberType`      |
| first address, e-mail  | `fe_users:<uid>`                | -                                                   |
| further address/e-mail | `<first column>:fe_users:<uid>` | -                                                   |

`telephoneNumberType` and `faxNumberType` are the extension configuration
options `profile.feuser.*`; "else" means an entry whose `type` is `''`.

The first address and e-mail entries keep the identifier they had before the
lists existed, so existing records are matched without a migration. Every
further entry is named by its first column, and the normaliser rejects two
entries of one list that share it, because both would write the same record.
It rejects a phone entry reading the column `phone` for the same reason: its
identifier `phone:fe_users:<uid>` is the one of the telephone records written
before ACE-365, which the telephone entry adopts, see below.
And it rejects an entry that maps no column rather than dropping it, which
would move the next entry to its position - at position 0 onto
`fe_users:<uid>`.

The identifier follows the map, not the data. An entry moved to the front of
its list writes onto the record `fe_users:<uid>`; the moved entry imports a new
record, and the record it wrote before is no longer synchronised - never
updated and never removed again. Changing an entry's first column does the
same. The synchronisation cannot tell a renamed column from a removed one.

The configured type is presentation data and therefore is not part of the
identity. Changing configuration must update neither an identifier nor create a
second record. A selectable existing type, including the undefined value `''`,
is an editor decision and remains unchanged. Synchronisation only replaces the
historical invalid type matching the source: `phone` for telephone and `fax`
for fax.

Both configuration options default to `business`. The shared stateless
resolver validates each value against the installation's phone-number type
list. If the configured value, including the default, is not available, it
returns `''`; imports are still performed.

## Legacy telephone identifiers

Before ACE-365, telephone records used `phone:fe_users:<uid>`. Runtime
synchronisation first looks for the canonical `telephone:` identifier and then
for the legacy identifier. A legacy match is reused and normalized. If both
exist in the same contract, the canonical record wins and the legacy record is
not merged or deleted because its provenance cannot be established safely.

There is deliberately no upgrade wizard. A record is repaired — identifier and
type together — the next time its frontend user is synchronized, which is the
same command that wrote it in the first place, so the population that has such
records is the population that runs the command. A bulk migration would have to
decide what the runtime deliberately refuses to decide: which of two colliding
rows is the real one, whether a soft-deleted or workspace row counts, and what
to write when the configured type is not selectable on that installation.

Six cases are therefore not repaired by a synchronization run, and an
installation that needs them corrected has to do so deliberately:

- profiles carrying `skip_sync = 1`;
- frontend users that are soft-deleted, which the provider excludes;
- profiles whose `tx_academicpersons_feuser_mm` row was removed;
- records on page ids excluded by the `--include-pids` / `--exclude-pids` the
  installation schedules;
- installations that ran an import once and never run it again;
- records whose `fe_users.telephone` has since been emptied — those are removed
  by the synchronization rather than repaired.

## See also

- [Database queries](database-queries.md) — the ordering rule the record match
  depends on.
- [Dependency injection](dependency-injection.md) — why the shared resolver and
  the mapper are stateless.
- [Validation settings](validation-settings.md) — the settings graph the map is
  part of, and how the package files are merged.
- [Testing](../testing/Index.md) — functional coverage across supported core
  versions and DBMSs.
