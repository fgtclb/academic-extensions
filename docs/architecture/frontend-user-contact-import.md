# Frontend-user contact import

`academic_persons` creates and updates profiles from `fe_users` through the
`academic:createprofiles` and `academic:updateprofiles` commands. Telephone and
fax values both become phone-number records on the imported profile contract;
physical addresses and email addresses follow their existing, separate paths.

## Identity and presentation are separate

The source field defines the import identity:

| Source field | Import identifier          | Configured type option                 |
|--------------|----------------------------|----------------------------------------|
| `telephone`  | `telephone:fe_users:<uid>` | `profile.fe_users.telephoneNumberType` |
| `fax`        | `fax:fe_users:<uid>`       | `profile.fe_users.faxNumberType`       |

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

The upgrade wizard applies the same policy in bulk. It reads without enable
field restrictions, validates the full identifier in PHP, and updates each row
with its own query builder. Hidden and deleted imported records are included.
The wizard is idempotent and leaves manual identifiers, valid types, and
canonical/legacy collisions intact except for correcting an exact invalid
legacy type.

## See also

- [Database queries](database-queries.md) — cross-DBMS rules used by the
  migration.
- [Dependency injection](dependency-injection.md) — why the shared resolver is
  stateless.
- [Testing](../testing/Index.md) — functional coverage across supported core
  versions and DBMSs.
