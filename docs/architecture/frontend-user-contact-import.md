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

## Contract relations are looked up, never imported

`contract.organisationalUnit` and `contract.functionType` are maps of their
own - `column`, `matchBy`, `create`, `storagePid` - and the normaliser takes
them out of the `contract` map before it checks the plain properties. A
relation with a column becomes a `FrontendUserSyncRelation` in
`FrontendUserSyncSettings::$relations`. A `matchBy` the relation does not
offer, `create: true` without a `storagePid`, a `create` that is no boolean and
a `storagePid` that is no page uid are problems like any other mistake of the
map, even on a relation without a column: the column may come from another
package later. The relations count as contract sources for `mapsContract()`,
`hasContractData()` and `getColumns()`.

`Profile\ContractRelationResolver`, stateless like the mapper, resolves the
value in two steps:

1. The `QueryBuilder` selects the candidates: `DeletedRestriction` only, so a
   hidden record is found rather than created again, the default language
   (`sys_language_uid IN (0, -1)`), the live workspace (`t3ver_wsid = 0`), and
   `ORDER BY uid`. PHP then takes the first candidate whose field is identical
   to the value. The database cannot decide that alone: `utf8mb4_unicode_ci`,
   the collation TYPO3 creates its MySQL and MariaDB tables with, ignores case
   and accents and compares `'X '` equal to `'X'`. PostgreSQL and SQLite do
   not, and one value would name different records on different databases.
2. The Extbase object is loaded by that uid with storage pages, language and
   enable fields ignored and the default language without overlays pinned,
   through the persistence manager like
   `AbstractProfileFactory::findFrontendUserIgnoringVisibility()`. An Extbase
   query was not used for the lookup itself: it respects storage pages and
   enable fields unless every setting is switched off, and the matching would
   still depend on the collation.

A created record is added and **persisted at once**. `updateProfileForUser()`
maps every profile of a frontend user before it persists them together, so a
record that is only added is invisible to the lookup for the second profile,
which would create it again. `persistAll()` writes everything pending in the
persistence manager at that moment. For the default factory that is the
profiles of the same user mapped before, and anything it removed. A custom
factory that adds its profile before it calls `applyContract()` gets the
profile and its contract written half-built, and completed by its own
`persistAll()` later. Two runs in parallel are not guarded against each other.
The commands are meant to run one after the other.

A value longer than the 255 characters of `unit_name`, `unique_name` and
`function_name` fails the insert on PostgreSQL and on MySQL and MariaDB in
strict mode, which stops the command. Without strict mode MySQL and MariaDB
cut it to 255 characters, so it never matches again and is created on every
run. SQLite stores it whole.

A hidden unit or function type is assigned but loads as `null` when the
contract is read again: the child query of the relation respects enable
fields. So every run sets it anew and writes the contract, and a hidden unit
is taken as newly joined by `AssignContractOrganisationalUnitSorting`, which
moves the contract to the end of the unit's list. A hidden unit is not
rendered, so nobody sees that order.

A mapped relation is owned by the synchronisation, like a mapped property: an
empty value (blanks only included) sets `null` without a lookup, and so does a
value that matches nothing while `create` is off. An unmapped relation is not
touched. `hasContractData()` reads a relation value the same way, trimmed, so
blanks are no contract data and `'0'` is. The employee type has no mapping:
`employee_type` is a `sys_category` relation without a type restriction,
`academic_persons` does not require `category_types`, and a title match across
every category would be ambiguous. A project sets it in a listener of the
mapped profile, see the next section.

`FrontendUserSyncRelationMappingTest`, with the fixture extension
`test_frontend_user_sync_relations`, gives every rule a record it can fail on:
a deleted unit and a new record of a workspace carry the searched unique name
at a lower uid than the live one, a value only a translation carries has to
create a new unit, a unit for all languages is found, and the searched
function name is carried by a lower-case record and two records written in
descending uid order. `FrontendUserSyncRelationMatchByNameTest` loads the other
map, `test_frontend_user_sync_relations_by_name`: units matched by name and
never created, function types created. Without `ORDER BY` PostgreSQL returns
the tie in write order, and without the PHP comparison MySQL and MariaDB return
the lower-case record. SQLite can fail neither of the two, so the class belongs
to the DBMS runs.

## Listeners take part, a factory stays optional

`AbstractProfileFactory` dispatches two events around the mapping, so a
project adds data or adjusts the result without replacing the factory. A copy
of the factory misses every later fix to the default one, the matching of the
contact records and the handling of hidden profiles among them. For one
frontend user the order is fixed:

1. `ChooseProfileFactoryEvent` chooses the factory (in the command services).
2. `BeforeProfileMappedFromFrontendUserEvent` carries the frontend user data,
   the action and, on update, the profile. A listener replaces the data or
   calls `skip()`.
3. The factory maps the data it got back.
4. `AfterProfileMappedFromFrontendUserEvent` carries the profile, the data the
   mapping used and the action, before anything is persisted.
5. The profile is persisted and `AfterProfileUpdateEvent` announces it.

On update, steps 2 to 4 repeat for every profile that is synchronised, which
leaves out `skip_sync` profiles, and step 5 persists and announces them
together. Every event of step 2 starts from the row of the frontend user rather
than from what a listener set for the profile before. A skip on update belongs
to the profile of its event: that profile is left out of the list the update
persists and announces, exactly like a `skip_sync` profile (ACE-490), and the
other profiles of the frontend user are updated. On creation, a skip returns before
the factory is asked, and `createProfileFromFrontendUser()` returning `null`
ends it after: nothing is added, persisted or announced, and the command takes
the next frontend user. Nothing records either outcome, so the next run of
`academic:createprofiles` selects the frontend user again. The data event is
not stoppable, so a listener after the one that skipped still runs and reads
`isSkipped()`. One event with a phase flag was rejected, because every listener
would branch on it. The first event is a `Before…Event` by the naming rule of
[Class design](class-design.md#extension-points): a listener may change what is
about to be mapped, and refuse it.

The abstract method returns `?Profile` now. A subclass declaring `Profile`
narrows the return type, which PHP accepts, so no factory breaks. A subclass
that overrides `createProfileForUser()` or `updateProfileForUser()` dispatches
the events only where it calls the parent method.

A key a listener adds is named `<source>.<key>` by convention, `ldap.room` for
example. `setFrontendUserData()` does not enforce it: it cannot tell an added
key from a real column a listener rewrites without comparing the arrays, and no
column of a TYPO3 table contains a dot, so a dotted key cannot hide one. The
map reads such a key like a column, and `assertColumnsExist()` refuses it when
it is missing, so a listener sets a key the map reads for every frontend user,
`''` when its source has nothing.

Listeners, like the factories, are shared services: one object per run. A
cache in a property would carry one frontend user's directory data into the
next, which is what several project factories did. The documented listeners
fetch per event and keep nothing.

`FrontendUserSyncEventsTest`, with the fixture extension
`test_frontend_user_sync_events`, runs both commands with a stand-in for a
directory: it adds `ldap.room`, which the fixture map reads as the contract
room, and `ldap.gender`. It skips a user who has left, but only after adding
that user's values, so a skip that is ignored shows in the profile, and it
skips one of the two profiles of another user, which shows that a skip on
update ends with the profile of its event. The listener of the mapped profile
rewrites the mapped last name, which only survives when it runs after the
mapping, and the first listener refuses data that already carries its keys,
which catches data leaking from one profile of a user into the next. A
listener of `ChooseProfileFactoryEvent` hands one user a factory that returns
`null`. A recording listener records every event of a run, and the test
asserts their order.

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

## Cleaning up profiles of inactive frontend users

`academic:cleanupprofiles` (ACE-215) is the one command that changes whether a
profile is shown. It is a command of its own rather than an option of
`academic:updateprofiles`, whose contract is the table above: the
synchronisation never writes visibility, and a scheduled update must not start
deleting profiles because an option was added to it.

`Provider\InactiveFrontendUserProfileProvider` selects the candidates with one
query: live profiles of the default language or of all languages
(`deleted = 0`, `sys_language_uid IN (-1, 0)`, `t3ver_wsid = 0`) with
`skip_sync = 0`, joined to their `tx_academicpersons_feuser_mm` rows and, by a
left join, to `fe_users`, ordered by profile and frontend user uid. The query
runs without restrictions, so disabled, expired and deleted frontend users
reach PHP, and so does a relation whose `fe_users` row is gone, with `null`
columns. PHP then applies the rule to the rows of each profile.

Only profiles the synchronisation manages are looked at: a profile with an
`import_identifier`, which `ProfileFactory` writes on every create and update,
or with a frontend user whose `tx_extbase_type` is the one
`FrontendUserProvider` reads. A custom factory that writes no identifier still
has its profiles cleaned up through the record type. The backend form shows
`skip_sync` only for a profile with an import identifier, so a profile reached
through the record type alone cannot be excluded by an editor. The manual
names `--exclude-pids` and an import identifier as the ways out. A profile an
editor linked to some other login is left alone, since the synchronisation
never touches it either.

The rule for each linked frontend user:

| A linked frontend user is | When                                                   |
|---------------------------|--------------------------------------------------------|
| deleted                   | `deleted = 1`, or its row is missing                   |
| inactive                  | not deleted, and `disable = 1` or `0 < endtime <= now` |
| active                    | anything else, a start time ahead included             |

One active frontend user keeps the profile. A profile whose users are all
deleted gets the `--deleted` action (`delete`, `hide` or `keep`), and one whose
users are all deleted or inactive, at least one of them inactive, the
`--disabled` action (`hide` or `keep`). "Now" is the date aspect of the
context, which is the time the run started.

The page options narrow which profiles are looked at, never the rule. A profile
is looked at when one of its frontend users is on an included page, or on any
page without `--include-pids`, and none is on an excluded page. A missing
`fe_users` row lies on no page. So a profile linked to a frontend user in an
excluded folder is left alone, and a profile linked only to missing rows is
cleaned up by a run without `--include-pids` only.

Every write is a DataHandler run of its own per profile, inside
`DataHandlerExecutionContext::runAsLiveBackendUser()`: a synthetic admin in the
live workspace, because an installation-wide cleanup has no business writing
into the workspace of whoever is logged in. Hiding is a datamap `hidden = 1` on
the default-language record, which `DataMapProcessor` carries into every
translation because the column is `l10n_mode => exclude`. Deleting is a cmdmap
`delete`, which the DataHandler cascades to the translations and to the
contracts with their contact records, and records in the history.

The runs carry no `ProfileWriteCorrelation` mark, so they behave like the same
change in the backend. A hidden profile is announced as
`AfterProfileUpdateEvent` with the origin `Backend`, except a profile set to
all languages, which a backend hide does not announce either, and
`DataHandlerHooks` flushes the list and detail cache tags of the plugins. A
delete is a command and is not announced, as in the backend.
`DataHandlerHooks::processCmdmap_postProcess()` flushes the same tags for a
deleted or restored profile, and for the parent of a translation, since the
detail view is tagged with the default-language uid. Before this change a
profile deleted in the backend stayed on cached list and detail pages: the core
flushes the page of the record and the tags of the table and the uid, and the
plugins carry neither unless the automatic cache tagging of the core
(`frontend.cache.autoTagging`) is on, which tags pages with the records they
show. It is on in instances set up on TYPO3 v13.3 or later and off in instances
upgraded from an earlier version.

A profile that is hidden already is left out, so it is neither listed nor
counted. Writing `hidden = 1` again would not add a history entry, since the
DataHandler drops unchanged values, but it would still flush the list cache on
every scheduled run. A hidden profile whose users are all deleted is deleted
all the same.

Page lists are checked before anything is read: a part that is no page uid
stops the command with exit code 2, because `intExplode()` would silently turn
`12;13` into `12` and exclude less than was meant.

The command never shows a profile again, and nothing records that it hid one
(ACE-229). A frontend user that is deleted and imported again gets a new uid, so
a mark on the old relation could not show the profile again anyway. A mark
column stays possible as an addition later.

Rejected: a raw SQL `UPDATE`, which one project shipped and which bypasses the
history, cache clearing and translations. Also rejected: evaluating the rule
per profile through Extbase, which is one query per profile and hides the very
rows the rule is about.

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
  and the relation lookup depend on.
- [Dependency injection](dependency-injection.md) — why the shared resolver and
  the mapper are stateless.
- [Validation settings](validation-settings.md) — the settings graph the map is
  part of, and how the package files are merged.
- [Managed fields](validation-settings.md#managed-fields-a-lock-per-record):
  how the fields the synchronisation writes are locked in the backend form of
  the records it wrote.
- [Testing](../testing/Index.md) — functional coverage across supported core
  versions and DBMSs.
