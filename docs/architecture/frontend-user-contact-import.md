# Frontend-user contact import

`academic_persons` creates and updates profiles from `fe_users` through the
`academic:createprofiles` and `academic:updateprofiles` commands. Telephone and
fax values both become phone-number records on the imported profile contract;
physical addresses and email addresses follow their existing, separate paths.

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
