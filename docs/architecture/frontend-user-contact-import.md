# Frontend-user contact import

`academic_persons` creates and updates profiles from `fe_users` through the
`academic:createprofiles` and `academic:updateprofiles` commands. Telephone and
fax values both become phone-number records on the imported profile contract;
physical addresses and email addresses follow their existing, separate paths.

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

The profile fields are not a fixed list. The lookup reads which of the four
the profile TCA declares as enable columns, so an installation that removes
one is not handed a field its table does not have, and an enable column added
later stays in effect.

Visibility is never written: a synchronised profile keeps all four values, and
a profile outside its window stays invisible in the frontend. The display paths
— the "show hidden records" option, the selected profiles and the detail view —
share a different helper, which lifts the hidden flag only.

## New records are stored on the page of their profile

`academic:createprofiles` stores the profile and its contract on the page of the
frontend user, because there is no profile yet whose page could be asked.
`academic:updateprofiles` stores a contract it adds to an existing profile on the
page of that profile, and the address, email address and phone numbers it adds to
a contract on the page of that contract, which for a new contract is the page of
the profile again. The profile may have been moved into a folder of its own, and
until ACE-843 the update stored the new records on the frontend user folder
instead. An editor with access to the profile folder only could then no longer
open the profile. Records stored that way are not moved by a later run.

## Cleaning up profiles of inactive frontend users

`academic:cleanupprofiles` (ACE-215) is the one command that changes whether a
profile is shown. It is a command of its own rather than an option of
`academic:updateprofiles`, whose contract is the table above: the
synchronisation never writes visibility, and a scheduled update must not start
deleting profiles because an option was added to it.

`Provider\InactiveFrontendUserProfileProvider` selects the candidates with one
query: profiles of the default language or of all languages (`deleted = 0`,
`sys_language_uid IN (-1, 0)`) with `skip_sync = 0`, joined to their
`tx_academicpersons_feuser_mm` rows and, by a left join, to `fe_users`, ordered
by profile and frontend user uid. The query runs without restrictions, so
disabled, expired and deleted frontend users reach PHP, and so does a relation
whose `fe_users` row is gone, with `null` columns. PHP then applies the rule to
the rows of each profile.

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
`Service\DataHandlerExecutionContext::runAsLiveBackendUser()`: a synthetic
admin in the live workspace, because an installation-wide cleanup has no
business writing into the workspace of whoever is logged in. It is the first
DataHandler write of `academic_persons` on this branch, and the context is a
small copy of `GeocodeWriteContext` of `academic_partners`, not the larger
class of the same name on `main`. Hiding is a datamap `hidden = 1` on the
default-language record, which `DataMapProcessor` carries into every
translation because the column is `l10n_mode => exclude`. Deleting is a cmdmap
`delete`, which the DataHandler cascades to the translations and to the
contracts with their contact records, and records in the history.

The runs behave like the same change in the backend: `DataHandlerHooks` flushes
the list and detail cache tags of the plugins after the hide. Neither the hide
nor the delete is announced as `AfterProfileUpdateEvent`, since a DataHandler
write is not announced on this branch.
`DataHandlerHooks::processCmdmap_postProcess()` flushes the same tags for a
deleted or restored profile, and for the parent of a translation, since the
detail view is tagged with the default-language uid. Before this change a
profile deleted in the backend stayed on cached list and detail pages: the core
flushes the page of the record and the tags of the table and the uid, and the
plugins carry neither unless the automatic cache tagging of the core is on,
which tags pages with the records they show. TYPO3 v12 has no such option, and
on v13 `frontend.cache.autoTagging` is on in instances set up on v13.3 or later
and off in instances upgraded from an earlier version.

A profile that is hidden already is left out, so it is neither listed nor
counted. Writing `hidden = 1` again would not add a history entry, since the
DataHandler drops unchanged values, but it would still flush the list cache on
every scheduled run. A hidden profile whose users are all deleted is deleted
all the same.

Page lists are checked before anything is read: a part that is no page uid
stops the command with exit code 2, because `intExplode()` would silently turn
`12;13` into `12` and exclude less than was meant. `academic:createprofiles` and
`academic:updateprofiles` refuse such a list the same way since ACE-845, and
since ACE-870 all three commands are handed the one stateless
`Command\PageListParser` instead of a copy of the reading each.

The command never shows a profile again, and nothing records that it hid one
(ACE-229). A frontend user that is deleted and imported again gets a new uid,
so a mark on the old relation could not show the profile again anyway. A mark
column stays possible as an addition later.

Rejected: a raw SQL `UPDATE`, which one project shipped and which bypasses the
history, cache clearing and translations. Also rejected: evaluating the rule
per profile through Extbase, which is one query per profile and hides the very
rows the rule is about.

## Identity and presentation are separate

The source field defines the import identity:

| Source field | Import identifier          | Configured type option               |
|--------------|----------------------------|--------------------------------------|
| `telephone`  | `telephone:fe_users:<uid>` | `profile.feuser.telephoneNumberType` |
| `fax`        | `fax:fe_users:<uid>`       | `profile.feuser.faxNumberType`       |

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

- [Database queries](database-queries.md) — the query builder rules this
  repository learned from released defects.
- [Dependency injection](dependency-injection.md) — why the shared resolver is
  stateless.
- [Testing](../testing/Index.md) — functional coverage across supported core
  versions and DBMSs.
