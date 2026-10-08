# Hidden records

The plugin option **Show hidden records** (`settings.showHiddenRecords`) lists
records whose hidden flag is set, while deleted records, start and end time and
frontend user groups keep deciding. Extbase offers that through the query
settings: `setIgnoreEnableFields(true)` with `setEnableFieldsToBeIgnored(['disabled'])`.
Those settings reach the query itself. On a translated page and TYPO3 v13 that
is not enough (ACE-857).

## What the query settings do not reach on TYPO3 v13

Extbase overlays a translation through `PageRepository`, which follows the
visibility aspect of the context, not the query settings. On TYPO3 v13 the
hidden translation of a hidden record is therefore not found, and the record
keeps its default language values: a visitor sees the English title on the
German page, with `fallbackType: strict` and `fallback` alike.

TYPO3 v14.3.7 mirrors the ignored enable fields of the query settings into the
context it overlays with, see `Typo3DbBackend::getObjectDataByQuery()` in
`cms-extbase`. v14.3.6 does not, and is below the supported floor of this
branch. The language statement itself follows the query settings on both
versions, so a hidden record is selected, only its overlay is missed.

## `HiddenRecordsFetcher`

[`academic-base/Classes/Persistence/HiddenRecordsFetcher.php`](../../packages/fgtclb/academic-base/Classes/Persistence/HiddenRecordsFetcher.php)
closes the gap on v13. It is a stateless service, internal to the academic
extensions, and does nothing on v14. A repository executes its query through it
instead of calling `execute()`:

```php
return $this->hiddenRecordsFetcher->execute($query);
```

For a query whose settings ignore the hidden flag, `execute()` returns a
[`HiddenRecordsQueryResult`](../../packages/fgtclb/academic-base/Classes/Persistence/HiddenRecordsQueryResult.php),
any other query gets the result of the core. The result is lazy like every
query result: it fetches when it is first used, within the overlay window, and
`getFirst()` runs its own limited query within the window as well. Counting it
stays a count query. A result that is never iterated is never fetched, the full
list behind a paginated one included, so the option costs no query of its own.

The window, `withinOverlayWindow()`, marks the context with the queried table,
as the aspect `academic-base.hidden-records-overlay`, runs the fetch and removes
the marker in a `finally` block. A fetch that fails after the visibility was
lifted leaves it restored there. Because the settings are read when the query
is executed, a listener of a query event that lifts the hidden flag is followed
as well.

The lift itself is the work of the listener
[`LiftVisibilityForHiddenRecordsOverlay`](../../packages/fgtclb/academic-base/Classes/EventListener/LiftVisibilityForHiddenRecordsOverlay.php),
on the two persistence events of Extbase. Extbase dispatches
`ModifyQueryBeforeFetchingObjectDataEvent`, clones the context for the
overlay, overlays the rows and dispatches
`ModifyResultAfterFetchingObjectDataEvent`. It maps the rows, eager relations
included, only after that. For a query of the marked table that ignores the
hidden flag, the listener lifts the visibility on the first event,
`includeHiddenPages` for `pages` and `includeHiddenContent` otherwise, keeps the
previous aspect in the marker, and restores it on the second event. So the
overlay finds the hidden translation, and the relations of the mapped objects
keep the visibility of the request, exactly as on v14. The marker keeps the
query that lifted, and only that query restores, so the result event of another
query in between cannot end the lift early.

A first version lifted the visibility around the whole fetch, and fetched the
full result in the repository. The relations
were then fetched inside the window as well, and a contacts list with the
option on rendered the contacts whose profile or contract is hidden, which
`AcademicContacts4PagesListPluginTest` pins as unresolved. A paginated list
then also fetched every record behind its page.

The repositories of jobs, partners, projects and contacts take it through their
constructor, `ProgramRepository` and `ProfileRepository` through an `inject*()`
method like their other collaborators:

| Repository                                       | Finders                                                                            |
|--------------------------------------------------|------------------------------------------------------------------------------------|
| `JobRepository` of `academic_jobs`               | `findByJobType()`, `findAllJobs()`                                                 |
| `PartnerRepository` of `academic_partners`       | `findByDemand()`                                                                   |
| `ProgramRepository` of `academic_programs`       | `findByDemand()`                                                                   |
| `ProjectRepository` of `academic_projects`       | `findByDemand()`                                                                   |
| `ProfileRepository` of `academic_persons`        | `findByDemand()`, `findByUidsWithContext()`, `findByFrontendUserIncludingHidden()` |
| `ContactRepository` of `academic_contacts4pages` | `findByPid()`                                                                      |

### A paginated list

`QueryResultPaginator` executes the query of the current page on its own, with
a plain result of the core. The list actions of the job, partner and profile
list therefore hand the paginated items to `fetch()`, which fetches that page
within the window, right after building the paginator. The full result the
repository returned stays unfetched, `JobRepositoryHiddenTranslationTest` pins
that.

## What it does not cover

- **A result executed elsewhere.** A listener of a list event that replaces the
  result with one of its own query has to execute it through the fetcher the
  same way.
- **The detail lookup of a hidden profile.** `findByUidIncludingHidden()` of
  `ProfileRepository` matches the uid of the default record, which a translated
  language statement does not select, measured on v13.4.35. That is a lookup
  question, not an overlay one, and is left to an issue of its own.

## See also

- [Core version aware code](core-version-aware-code.md#a-switch-inside-a-class) -
  the version switch of the fetcher
- [Dependency injection](dependency-injection.md)
- [Database queries](database-queries.md)
