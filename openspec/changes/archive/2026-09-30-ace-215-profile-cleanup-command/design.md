## Context

- `academic_persons` has three commands: `academic:createprofiles`,
  `academic:updateprofiles` and `academic:persons:settings:migrate`
  (`Classes/Command/`). All three are registered with `console.command` tags
  in `Configuration/Services.yaml`.
- The update never writes visibility
  (`Classes/Profile/AbstractProfileFactory.php`, `updateProfileForUser()`).
- The synchronisation reads frontend users of the record type
  `Tx_Academicpersonsedit_Domain_Model_FrontendUser` only
  (`FrontendUserProvider`), and `ProfileFactory` writes the import identifier
  `fe_users:<uid>` on every create and update. The backend form shows
  `skip_sync` only for a profile with an import identifier.
- The list and detail plugins are tagged `profile_list_view` and
  `profile_detail_view_<uid>`. `DataHandlerHooks` flushes them after a save,
  never after a delete. Only the automatic cache tagging of the core
  (`frontend.cache.autoTagging`) tags pages with the records they show, and
  it is off in instances upgraded from a version before v13.3.
- Profiles and frontend users are linked through
  `tx_academicpersons_feuser_mm`, with `uid_local` = profile and
  `uid_foreign` = frontend user (`Classes/Provider/FrontendUserProvider.php`).
- `DataHandlerExecutionContext::runAsBackendUser()` and
  `runAsLiveBackendUser()` exist on `main`
  (`Classes/Service/DataHandlerExecutionContext.php`). Neither exists on
  branch `2`.
- `hidden` of the profile is `l10n_mode => exclude`, so a DataHandler write on
  the default-language record reaches every translation.

## Goals / Non-Goals

**Goals:**

- One selection query, deterministic order, and every write through the
  DataHandler.

**Non-Goals:**

- Remembering why a profile was hidden.

## Decisions

### Selection in a stateless provider

A `final readonly class InactiveFrontendUserProfileProvider` returns
`ProfileCleanupCandidate` objects ordered by profile uid. One query with the
TYPO3 `QueryBuilder` reads the profile table, inner-joined to the MM table and
left-joined to `fe_users`, ordered by profile uid and frontend user uid. PHP
then applies the rule to the rows of each profile.

- Only profiles the synchronisation manages are looked at: a profile with an
  import identifier, or with a linked frontend user of the record type the
  synchronisation reads. A custom factory that writes no identifier is still
  covered through the record type. A profile an editor linked to another
  login is left alone, as the synchronisation leaves it. `skip_sync` is
  shown only with an import identifier, so a profile reached through the
  record type alone has no switch for editors. The manual names
  `--exclude-pids` and an import identifier for it. Rejected: requiring an
  import identifier, which would drop the profiles of project factories
  that write none.
- The query runs with all restrictions removed, so that disabled, expired and
  soft-deleted users reach PHP. A missing `fe_users` row arrives with `null`
  columns and counts as deleted.
- The conditions on the profile are written out: `deleted = 0`,
  `sys_language_uid IN (-1, 0)`, `t3ver_wsid = 0` and `skip_sync = 0`. A
  profile set to all languages can be linked to a frontend user as well.
- A start time in the future does not make a user inactive: that user is about
  to become active, not gone.
- The page options narrow which profiles are looked at, never the rule. A
  profile is looked at when one of its users lies on an included page (any page
  without `--include-pids`) and none lies on an excluded page. A missing row
  lies on no page. So `--exclude-pids` protects a profile of a user in that
  folder, and a profile linked only to missing rows is cleaned up by a run
  without `--include-pids` only. The pid lists are compared in PHP, which
  needs no quoting. A list with a part that is no page uid exits with `2`:
  `intExplode()` would turn `12;13` into `12` and exclude less than meant.

A profile is "all deleted" when every linked user is deleted. It is
"inactive" when every linked user is deleted, disabled or has
`endtime > 0 AND endtime <= now`, and at least one is not deleted. The
deleted action applies only to the first group. "Now" is the date aspect of
the context.

Rejected: evaluating this in PHP per profile through Extbase. That means one
query per profile, and Extbase hides the rows the rule is about. Also
rejected: counting in SQL with `CASE` aggregates. The rows of the candidate
profiles are few, and the rule reads more clearly in PHP.

### Writes through the DataHandler

Each profile is written in a DataHandler run of its own, inside
`runAsLiveBackendUser()`: a synthetic admin in the live workspace, since an
installation-wide cleanup must not write into the workspace of whoever is
logged in.

- a datamap `hidden = 1` on the default-language profile, which the
  DataHandler and `DataMapProcessor` carry to its translations;
- a cmdmap `delete` for deletion, which the DataHandler cascades to
  translations and inline children, and records in the history.

The runs carry no `ProfileWriteCorrelation` mark, so they behave like the same
change in the backend: a hidden profile is announced with the origin
`Backend`, a delete is a command and is not announced. `Internal` was
rejected, because it means "the code that started the run announces the
update", and nobody would. A profile that is hidden already is left out, so it
is neither listed nor counted and a scheduled run does not flush the list
cache for it. A hidden profile whose users are all deleted is deleted.

A run per profile lets the command name the profile a DataHandler error
belongs to, go on with the next one and exit with `1` in the end. An unknown
`--disabled` or `--deleted` value exits with `2` before anything is read.

Rejected: a raw SQL update, which is one project's approach and bypasses
history, cache and translations. Also rejected: folding this into
`academic:updateprofiles`, whose documented contract is "never change
visibility".

### Cache tags on delete

`DataHandlerHooks` is registered as a `processCmdmapClass` as well, and
`processCmdmap_postProcess()` flushes `profile_list_view` and
`profile_detail_view_<uid>` for a deleted or restored profile, and the detail
tag of the parent for a translation. This fixes the backend delete too, which
kept a deleted profile on cached pages until they expired. Rejected: flushing
from the command only, which leaves the backend path broken.

### Registration matches the sibling commands

Register the command with a `console.command` tag in `Services.yaml`, like
the three existing commands of the extension.

Rejected: Symfony's `#[AsCommand]`, which would make it the only command of
the extension registered differently.

### Decided: re-enabling stays manual

The command never shows a profile again, and no marker column records that
the cleanup hid it. ACE-229 stays open for a follow-up.

The case behind ACE-229 is frontend users that were deleted and imported
again: they get new `fe_users` uids, so a marker on the old relation could not
re-show their profiles anyway. Not remembering why a profile was hidden is
already a non-goal, and a marker column can be added later as an additive
follow-up without migrating anything. Rejected: a marker column set together
with `hidden = 1` in this change. Also rejected: deriving the reason from
`sys_history` or `sys_log`, which can be pruned.

## Risks / Trade-offs

- [Mass deletion after an LDAP outage disabled all users] → Defaults are
  documented, `--dry-run` is recommended as the first scheduler run, and
  deletions stay restorable from the history.
- [Translations of `hidden` depend on the TCA `l10n_mode`] → Verified by a
  functional test on a translated profile before relying on it.

## Open Questions

None.
