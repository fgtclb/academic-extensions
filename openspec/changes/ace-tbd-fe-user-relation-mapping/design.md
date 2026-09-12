## Context

- `Contract` has `?OrganisationalUnit $organisationalUnit`,
  `?FunctionType $functionType` and `?Category $employeeType`
  (`academic-persons/Classes/Domain/Model/Contract.php:23-27`).
  `OrganisationalUnit` has `unitName` and `uniqueName`, and `FunctionType`
  has `functionName`.
- `employee_type` is a TCA `category` field, `oneToOne`, with no type
  restriction (`Configuration/TCA/tx_academicpersons_domain_model_contract.php:172-182`).
- The `type` column of `sys_category` is added by `category_types`
  (`packages/fgtclb/typo3-category-types/Configuration/TCA/Overrides/sys_category.php`).
  `academic_persons` does not require that package (`composer.json`). The
  candidate's `categoryType` option would therefore only work when it is
  installed. The candidate did not mention this.
- The default factory never sets any of the three relations
  (`Classes/Profile/ProfileFactory.php:139-174`).

## Goals / Non-Goals

**Goals:**

- Deterministic lookups on every DBMS, and creation only where the storage
  page is explicit.

**Non-Goals:**

- Value maps or transformations of the source (see
  `ace-tbd-fe-user-sync-data-events`).
- An employee type mapping (see the decision below).

## Decisions

### Configuration shape

```yaml
frontendUserSync:
  contract:
    organisationalUnit: { column: '', matchBy: uniqueName, create: false, storagePid: 0 }
    functionType: { column: '', matchBy: functionName, create: false, storagePid: 0 }
```

An empty `column` means "not mapped". The normaliser rejects:

- a `matchBy` outside the listed fields;
- `create: true` with `storagePid: 0`.

Rejected: creating records without a configured storage page, which is what
one project does today (page 1).

### Decided: no employee type mapping in the first version

The first version ships the `organisationalUnit` and `functionType` mappings
only. There is no `employeeType` entry and no `categoryType` option; the
synchronisation leaves the contract's employee type alone.

`academic_persons` does not require `category_types`, so a `categoryType`
option would need a check for an optional package. Without it, the
`employee_type` relation has no type restriction, and a title match across
all system categories is ambiguous. The one analysed project that needs a
typed employee-type category restricts it to its own category type and has
its own lookup. A listener of `AfterProfileMappedFromFrontendUserEvent`
(`ace-tbd-fe-user-sync-data-events`) covers that without upstream
configuration: it reads the source value from `getFrontendUserData()`,
resolves the category with its own query and sets it on the contracts of
`getProfile()`. The documentation shows that recipe.

Rejected: keeping `categoryType`, with the normaliser rejecting it when
`sys_category` has no `type` column, which carries an optional-package check
for a single project. Also rejected: an `employeeType` entry matched by title
across all categories, which is ambiguous as soon as two category types
share a title.

### Lookups through the QueryBuilder in a stateless resolver

A `final readonly class ContractRelationResolver` resolves uids with the
TYPO3 `QueryBuilder`:

- a `DeletedRestriction` only, so hidden records still match and are not
  duplicated;
- live workspace and default language (`sys_language_uid IN (0, -1)`);
- a named string parameter for the value;
- `ORDER BY uid` and `setMaxResults(1)`.

The contract receives the object through the Extbase repository by uid.
Creation goes through the persistence manager like every other factory write.

Rejected: Extbase queries for the lookup. They respect storage pages and
enable fields by default, and they give less control over the ordering.

### Empty source clears the relation

A mapped relation is owned by the synchronisation, the same as a mapped
contact record, so an empty source sets it to `null`.

## Risks / Trade-offs

- [Two units with the same unique name] → The lowest uid wins
  deterministically. The documentation recommends unique values.
- [Creation races between parallel runs] → The commands run sequentially.
  Parallel runs are out of scope and documented.
- [A project that needs the employee type depends on the event of
  `ace-tbd-fe-user-sync-data-events`] → Accepted; the recipe is documented,
  and a mapping can be added later if more projects ask for it.

## Open Questions

None.
