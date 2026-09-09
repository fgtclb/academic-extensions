## Context

See `proposal.md` for the defect and motivation. The default profile factory currently uses the stored type both as the record type and as part of the import identity. Its shared lifecycle also means new mutable configuration state must not be added to the factory. Existing installations can contain invalid `phone` and `fax` values and the legacy telephone identifier `phone:fe_users:<uid>`.

The implementation must use APIs shared by TYPO3 v12 and v13. The database migration must behave consistently on SQLite, MariaDB, MySQL, and PostgreSQL.

## Goals / Non-Goals

**Goals:**

- Keep configuration resolution and validation identical for live synchronisation and migration.
- Make source identity independent from the configured type.
- Repair unambiguous legacy data without overwriting selectable editor corrections.
- Repair existing records where synchronisation finds them, without a bulk migration.
- Keep each of the three planned commits independently coherent.

**Non-Goals:**

- Adding mutable configuration state to the shared abstract factory.
- Treating an empty configured type as disabling an import.
- Migrating physical-address or email-address types.
- Merging or deleting pre-existing duplicate records.

## Decisions

### Use one stateless configuration resolver

An internal resolver reads `profile/feuser/telephoneNumberType` and `profile/feuser/faxNumberType`, uses `business` when an option is absent, and validates the value against `PhoneNumberTypes::getAll()`. The profile factory depends on it.

This avoids duplicated validation and avoids protected mutable properties on `AbstractProfileFactory`. Storing resolved options on the shared factory was rejected because the repository's dependency-injection rules identify that existing state as a design debt that must not be extended.

### Keep source field and type as separate inputs

The factory uses `telephone` or `fax` to read `fe_users` and build the import identifier. The resolver supplies the stored type separately. Existing records are matched by identifier alone because a type is data, not identity.

A selectable existing type, including `''`, is preserved. Only the matching historical invalid value (`phone` for telephone, `fax` for fax) is replaced. Enforcing configuration on every update was rejected because it would overwrite valid editor corrections.

### Make the legacy lookup self-healing

Telephone lookup tries the canonical identifier first and the legacy `phone:` identifier second. A legacy match is reused and normalized. If both records exist, the canonical record wins and the legacy record remains untouched; automatic merging or deletion cannot establish which data an editor intended to retain.

Fax needs no identifier fallback because its source identifier does not change.

### Migrate row by row with exact PHP validation

No upgrade wizard is shipped. A bulk migration would have to decide what synchronisation deliberately refuses to decide - which of two colliding records is the real one, whether soft-deleted and workspace rows take part, and what to write where the configured type is not selectable on that installation - and it would touch the majority of records that the next synchronisation run repairs anyway.

Using database-specific regular expressions or string replacement was rejected because it would add avoidable DBMS differences.

### Split the delivery into three commits

The telephone-only contract fix, the configuration, the resolver and the synchronisation land as one commit, with the OpenSpec archive as the second and last one. Two changelog entries are written: a `Feature-` for the configuration options and an `Important-` for the changed stored identifier and type.

No commit is created until the user has run the final test matrix and explicitly approves committing.

## Risks / Trade-offs

- **A valid editor correction can differ from configuration indefinitely** → This is intentional; configuration applies to new records and exact legacy-invalid values only.
- **A canonical/legacy collision leaves an extra record** → Preserve both to avoid data loss and document that neither is merged, because the provenance of the legacy record cannot be established safely.
- **Records outside the synchronised population keep the legacy values** → Named in the `Important-` changelog entry, so an installation can decide deliberately.
- **A missing `business` entry makes the default unavailable** → Validation returns `''`, which is the valid undefined TCA item.
- **Broad SQL `LIKE` matching can select unrelated rows** → Require an exact identifier match in PHP before deciding that a row is actionable.

## Migration Plan

1. Deploy the new configuration and synchronisation behaviour with defaults in place.
2. Let the next `academic:updateprofiles` run repair existing records - identifier and type together - as it reaches them.
4. Rollback requires restoring code and configuration only; the canonical identifier and selectable migrated types remain valid data under the previous version, so data rollback is unnecessary.

## Backport adaptations

- `final readonly class` is PHP 8.2 syntax and this branch supports PHP 8.1, so the resolver is a `final class` whose promoted properties carry `readonly` individually.
- `PhoneNumberRepository::findByContractIncludingHidden()` orders by `sorting` alone on this branch; `main` gained the `uid` tiebreaker through a separate change. The record match adopts the first row of that result, so the tiebreaker is part of this change here.
- The language files of this branch are indented with tabs, the changelog entries belong under `Documentation/Changelog/2.4/`, and the tracked development instances are `core-12` and `core-13`.
