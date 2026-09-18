## 1. Backport analysis

- [x] 1.1 Every file the `main` change touches was diffed against this branch
      before anything was planned. The three partials and their `page.tsconfig`
      registrations are **byte-identical** to `main`; `CategoryCollection` is
      too. `CategoryTypeRegistry`, `CategoryType` and `CategoryRepository`
      differ, and one of those differences matters - see 6.1. The findings are
      in `design.md`; the working notes are not part of the repository.

## 2. Shared renderer in category_types

- [x] 2.1 `Backend\PageCategorySummaryRenderer` added as a **`final class`**
      with promoted `private readonly` properties, and
      `Resources/Private/Templates/PageCategorySummary.html`. Verified
      equivalent to the `main` file: reversing the two PHP 8.1 adaptations
      produces it byte for byte, apart from the docblock note that says why
      they are there.
- [x] 2.2 The renderer's functional tests and the template override test
      ported, the latter with a new fixture extension. Nine tests.
      **No assertion depends on the order of categories inside a type row** -
      see the risk in `design.md`.

## 3. Listeners per extension

- [x] 3.1 `EventListener\AddPageModuleCategorySummary` added to
      `academic_programs` (page type 20, group `programs`) and registered with
      the **`event.listener` tag** in `Configuration/Services.yaml`.
- [x] 3.2 The same for `academic_projects` (30) and `academic_partners` (40).
      **Red proof:** removing the tag turns **three** of the four tests of
      `PageModuleCategorySummaryTest` red in each extension - the same count as
      on `main`, and for the same reason: the fourth asserts an empty header for
      a standard page, which an unregistered listener also produces. Counting
      the whole change: **23 new tests, 18 of which discriminate** - 9/7 in
      `category_types`, 6/5 in `academic_programs` (the two integrator-type
      tests are extra and both discriminate), 4/3 in each of the other two.
- [x] 3.3 The integrator-added type test of `academic_programs`, with its
      fixture extension.
- [x] 3.4 The `templates.typo3/cms-backend.academic-*` line removed from the
      three `Configuration/page.tsconfig`, the three `Doktype*.html` partials
      deleted, and `academic-projects/ext_typoscript_setup.typoscript` with
      them. A grep covering **both** registration mechanisms leaves only
      historical mentions.
- [x] 3.5 The class comment of `CategoryViewHelperTest` corrected, keeping its
      sentence about the class differing between the branches.
- [x] 3.6 **Not in the plan, and branch specific.**
      `academic-programs/Tests/Functional/SiteSet/InstallationWideRegistrationTest`
      had a test `backendTemplateOverrideIsRegisteredOnce()` pinning the very
      registration this change removes - it exists on this branch only and was
      written to keep a once-duplicated registration from coming back. It is
      `noBackendTemplateOverrideIsRegistered()` now and asserts the absence,
      which keeps the guard pointed at the same regression: a re-appearing
      entry means somebody restored a registration whose target is gone.

## 4. Documentation

- [x] 4.1 `docs/architecture/page-module-category-summary.md` written for this
      branch - v12 facts, the `event.listener` tag rather than the attribute -
      linked from `docs/architecture/Index.md` in the page table and the
      short-version list. The `main` page links to `docs/architecture/icons.md`,
      which does not exist here; that link is dropped rather than carried over
      dead.
- [x] 4.2 `Documentation/Changelog/2.4/Important-PageModuleCategorySummary.rst`
      in the three extensions and `Feature-PageModuleCategorySummary.rst` in
      `category_types`. Each extension states **its own** history: only
      `academic_programs` ever rendered the table. The manual chapter of
      `category_types` shows the `Services.yaml` tag and a plain `final class`,
      because its `main` example uses two constructs v12 and PHP 8.1 do not
      have.
      Verified in the **rendered HTML**, not only through the gate: `<dt>` is 0
      in all four entries.

## 5. Commits

- [x] 5.1 Five commits, as on `main`: the renderer, then one per extension,
      then the archive. Same issue keys, because each covers both branches.

## 6. What the backport analysis had to catch

- [x] 6.1 **`CategoryRepository::findByGroupAndPageId()` has no `ORDER BY` on
      this branch.** ACE-482 and ACE-491 added it on `main` only. The order of
      categories inside a type row is therefore whatever the DBMS returns, so
      no test may assert one. The `main` tests happen to assert presence and
      counts, which is why they port unchanged - that is luck, and it is
      written down rather than left to be rediscovered. Fixing the ordering is
      ACE-431.
- [x] 6.2 `#[AsEventListener]` does not exist on v12; `final readonly class` is
      PHP 8.2 and this branch floors at 8.1. Both are handled above.
- [x] 6.3 `ModuleTemplateFactory` is public on v12 through
      `cms-backend/Configuration/Services.yaml`, not through
      `#[Autoconfigure]` as on v13 - so `$this->get()` works in the tests, for
      a different reason than on `main`.

## 7. Definition of done

- [x] 7.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` green for both core versions, each after its own install:
      v12.4.45 unit 667 / functional 1343, v13.4.35 unit 667 /
      functional 1454. The new tests also run green on **postgres** for both.
- [x] 7.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 7.3 `docs/` and the four `Documentation/` changelogs updated.
- [x] 7.4 Archived as the last commit of the pull request.
