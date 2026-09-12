## Context

- `academic_persons` has three commands: `academic:createprofiles`,
  `academic:updateprofiles` and `academic:persons:settings:migrate`
  (`Classes/Command/`). All three are registered with `console.command` tags
  in `Configuration/Services.yaml:37-53`.
- The update never writes visibility
  (`Classes/Profile/AbstractProfileFactory.php:146-147`).
- Profiles and frontend users are linked through
  `tx_academicpersons_feuser_mm`, with `uid_local` = profile and
  `uid_foreign` = frontend user (`Classes/Provider/FrontendUserProvider.php`).
- `DataHandlerExecutionContext::runAsBackendUser()` exists on `main`
  (`Classes/Service/DataHandlerExecutionContext.php:70`). It does **not**
  exist on branch `2`.

## Goals / Non-Goals

**Goals:**

- One selection query, deterministic order, and every write through the
  DataHandler.

**Non-Goals:**

- Remembering why a profile was hidden.

## Decisions

### Selection in a stateless provider

A `final readonly class InactiveFrontendUserProfileProvider` returns profile
uids ordered by `uid`, using the TYPO3 `QueryBuilder` on the MM table joined
to `fe_users` and the profile table.

- The restrictions are removed on `fe_users`, so that disabled, expired and
  soft-deleted users are visible to the query.
- A missing `fe_users` row counts as deleted.
- The profile table keeps its `DeletedRestriction` and is limited to live
  default-language rows with `skip_sync = 0`.
- Pid lists are quoted with `quoteArrayBasedValueListToIntegerList()`.

A profile is "all deleted" when every linked user is deleted. It is
"inactive" when every linked user is deleted, disabled or has
`endtime > 0 AND endtime <= now`, and at least one is not deleted. The
deleted action applies only to the first group.

Rejected: evaluating this in PHP per profile through Extbase. That means one
query per profile, and Extbase hides the rows the rule is about.

### Writes through the DataHandler

The command runs `runAsBackendUser()`:

- a datamap `hidden = 1` on the default-language profile, which the
  DataHandler and `DataMapProcessor` carry to its translations;
- a cmdmap `delete` for deletion, which the DataHandler cascades to
  translations and inline children.

Rejected: a raw SQL update, which is one project's approach and bypasses
history, cache and translations. Also rejected: folding this into
`academic:updateprofiles`, whose documented contract is "never change
visibility".

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
