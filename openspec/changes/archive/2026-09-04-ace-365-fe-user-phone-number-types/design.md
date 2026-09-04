## Context

See `proposal.md` for the defect and motivation. The default profile factory currently uses the stored type both as the record type and as part of the import identity. Its shared lifecycle also means new mutable configuration state must not be added to the factory. Existing installations can contain invalid `phone` and `fax` values and the legacy telephone identifier `phone:fe_users:<uid>`.

The implementation must use APIs shared by TYPO3 v13 and v14. The database migration must behave consistently on SQLite, MariaDB, MySQL, and PostgreSQL.

## Goals / Non-Goals

**Goals:**

- Keep configuration resolution and validation identical for live synchronisation and migration.
- Make source identity independent from the configured type.
- Repair unambiguous legacy data without overwriting selectable editor corrections.
- Avoid duplicates when an installation has not run the upgrade wizard.
- Keep each of the three planned commits independently coherent.

**Non-Goals:**

- Adding mutable configuration state to the shared abstract factory.
- Treating an empty configured type as disabling an import.
- Migrating physical-address or email-address types.
- Merging or deleting pre-existing duplicate records.

## Decisions

### Use one stateless configuration resolver

An internal resolver reads `profile/fe_users/telephoneNumberType` and `profile/fe_users/faxNumberType`, uses `business` when an option is absent, and validates the value against `PhoneNumberTypes::getAll()`. Both the profile factory and upgrade wizard depend on it.

This avoids duplicated validation and avoids protected mutable properties on `AbstractProfileFactory`. Storing resolved options on the shared factory was rejected because the repository's dependency-injection rules identify that existing state as a design debt that must not be extended.

### Keep source field and type as separate inputs

The factory uses `telephone` or `fax` to read `fe_users` and build the import identifier. The resolver supplies the stored type separately. Existing records are matched by identifier alone because a type is data, not identity.

A selectable existing type, including `''`, is preserved. Only the matching historical invalid value (`phone` for telephone, `fax` for fax) is replaced. Enforcing configuration on every update was rejected because it would overwrite valid editor corrections.

### Make the legacy lookup self-healing

Telephone lookup tries the canonical identifier first and the legacy `phone:` identifier second. A legacy match is reused and normalized. If both records exist, the canonical record wins and the legacy record remains untouched; automatic merging or deletion cannot establish which data an editor intended to retain.

Fax needs no identifier fallback because its source identifier does not change.

### Migrate row by row with exact PHP validation

The wizard uses unrestricted, ordered QueryBuilder reads to narrow candidates and validates the full identifier format in PHP. Each actionable row is updated with a QueryBuilder that creates and binds its own parameters.

The wizard normalizes a legacy telephone identifier when the same contract has no canonical record. It replaces only the exact invalid legacy type and preserves all selectable types. A collision prevents identifier normalization but never causes deletion. `updateNecessary()` evaluates the same actions as execution, making repeated runs honest and idempotent.

Using database-specific regular expressions or string replacement was rejected because it would add avoidable DBMS differences.

### Split the delivery into three commits

The telephone-only contract bug is isolated first. Configuration, resolver, synchronisation and runtime fallback follow as one feature commit. The upgrade wizard and its fixtures form the third commit. The feature changelog is introduced with the feature and extended with migration instructions in the third commit.

No commit is created until the user has run the final test matrix and explicitly approves committing.

## Risks / Trade-offs

- **A valid editor correction can differ from configuration indefinitely** → This is intentional; configuration applies to new records and exact legacy-invalid values only.
- **A canonical/legacy collision leaves an extra record** → Preserve both to avoid data loss and document that the wizard does not merge duplicates.
- **The wizard may be skipped** → The runtime fallback repairs legacy telephone records as they are synchronised.
- **A missing `business` entry makes the default unavailable** → Validation returns `''`, which is the valid undefined TCA item.
- **Broad SQL `LIKE` matching can select unrelated rows** → Require an exact identifier match in PHP before deciding that a row is actionable.

## Migration Plan

1. Deploy the new configuration and synchronisation behaviour with defaults in place.
2. Run the registered upgrade wizard to migrate existing unambiguous records in bulk.
3. Let the runtime fallback normalize legacy telephone records missed because the wizard was skipped.
4. Rollback requires restoring code and configuration only; the canonical identifier and selectable migrated types remain valid data under the previous version, so data rollback is unnecessary.
