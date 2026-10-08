# Hidden records

The plugin option **Show hidden records** (`settings.showHiddenRecords`) lists
records whose hidden flag is set, while deleted records, start and end time and
frontend user groups keep deciding. Extbase offers that through the query
settings: `setIgnoreEnableFields(true)` with `setEnableFieldsToBeIgnored(['disabled'])`.
Those settings reach the query itself, and on a translated page that is not
enough (ACE-826).

## What the query settings do not reach

Measured with the translated list tests of `academic_jobs`, `academic_programs`,
`academic_projects` and `academic_partners`. A hidden record and its hidden
translation, on a German page:

| Step                                       | TYPO3 v12                  | TYPO3 v13                  |
|--------------------------------------------|----------------------------|----------------------------|
| Language statement, `fallbackType: strict` | leaves the record out      | follows the query settings |
| Overlay, `strict` and `fallback`           | keeps the default language | keeps the default language |
| What a visitor sees with `strict`          | the record is missing      | the English title          |
| What a visitor sees with `fallback`        | the English title          | the English title          |

- **The overlay.** Extbase overlays a translation through `PageRepository`, which
  follows the visibility aspect of the context, not the query settings. The
  hidden translation is not found, and the record keeps its default language
  values.
- **The language statement of TYPO3 v12.** With overlays that leave untranslated
  records out (`OVERLAYS_ON`, and `OVERLAYS_ON_WITH_FLOATING` of
  `fallbackType: strict`), the query selects the translations whose default
  record a subquery finds. On v12 that subquery is built with the default
  restrictions of the query builder and excludes hidden default records
  unconditionally. TYPO3 v13 applies the visibility of the query settings to it.

## `HiddenRecordsQueryTrait`

[`academic-base/Classes/Domain/Repository/HiddenRecordsQueryTrait.php`](../../packages/fgtclb/academic-base/Classes/Domain/Repository/HiddenRecordsQueryTrait.php)
closes both gaps. It is internal to the academic extensions, and the job,
program, project and partner repositories use it:

```php
if ($demand->getShowHiddenRecords() === true) {
    $this->includeHiddenRecords($query);
}
// ... constraints, matching(), orderings ...
$this->matchTranslationsOfHiddenRecords($query);
$programs = $query->execute();
$this->fetchIncludingHiddenRecords($programs);
return $programs;
```

- `includeHiddenRecords()` sets the query settings, as before.
- `matchTranslationsOfHiddenRecords()` replaces the language statement on v12
  for the two overlay types above: it matches the translations of the language
  directly and turns the language restriction of the query settings off. The
  overlay still reads the default record of each translation and drops one whose
  default record is deleted. A translation whose default record is outside its
  start and end time is left out by uid: the default records outside their time
  window that have a translation in the language are read first, with a query
  builder that only excludes deleted rows, and only when the table has those
  columns and no preview of scheduled records is active. It does nothing on v13.
- `fetchIncludingHiddenRecords()` fetches the result while the visibility aspect
  includes hidden records of the queried table, `includeHiddenPages` for a query
  on `pages` and `includeHiddenContent` otherwise, and restores the aspect in a
  `finally` block. The repository still returns the query result, iterating it
  later reuses the fetched objects.

Both of the last two leave a query that does not include hidden records alone,
so a repository calls them unconditionally.

## What it does not cover

- **A paginated result.** The result is fetched once, inside the window. A
  paginator that executes the query again with a limit and an offset runs
  outside of it. None of the four lists paginates on this branch.
- **`fallbackType: fallback` on v12 where a record and its translation differ
  in visibility.** The mixed language statement of v12 finds translated records
  through subqueries that exclude hidden rows, and the trait does not replace
  it: a replacement would need the uids of every translated record of the table
  as a parameter list, which is not safe for `pages`. A visible default record
  with a hidden translation is listed twice, a hidden default record with a
  visible translation is missing. With `strict`, and on v13 in both modes, each
  is listed once with its translation. The tests pin the v12 result.
- **The frontend user groups of the default record on v12.** The replacement
  checks start and end time of the default record, not its groups. A page
  translation carries the groups of its default page, the job table has none.
- **Eager relations** are fetched inside the window, for every record of the
  result, so hidden records of the same kind are included there as well. In the
  job list that is a hidden image reference of any listed job, a visible one
  included, while the option is on. The page based lists only lift hidden pages,
  so their category and file references keep the visibility of the request.
  Lazy relations load later with the visibility of the request.
- **`academic_persons` and `academic_contacts4pages`** ship the same plugin
  option with queries of their own, which do not use the trait.

## See also

- [Core version aware code](core-version-aware-code.md#a-switch-inside-a-class) -
  the version switch of the trait
- [Database queries](database-queries.md)
- [Translation synchronization](translation-synchronization.md)
