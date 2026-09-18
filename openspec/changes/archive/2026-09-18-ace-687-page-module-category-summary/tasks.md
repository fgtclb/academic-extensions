## 1. Shared renderer in category_types

- [x] 1.1 Added `Backend\PageCategorySummaryRenderer` (`final readonly`,
      autowired) and `Resources/Private/Templates/PageCategorySummary.html`,
      rendering type title, icon, categories, the hidden marker and the
      "not set" note. `Tests/Functional/Backend/PageCategorySummaryRendererTest`
      covers it against the `testing` group of the existing fixture extension:
      both type titles, both categories, the hidden marker, the not-set note,
      a page of another type, a request without a page and an unregistered
      group. Red proofs: dropping the doktype guard turns
      `aPageOfAnotherTypeGetsNoSummaryAlthoughItCarriesCategories` red,
      removing the `<f:else>` branch turns the not-set test red.
- [x] 1.2 `PageCategorySummaryTemplateOverrideTest` registers
      `templates.fgtclb/category-types.test-override` through the
      `Configuration/page.tsconfig` of the new fixture extension
      `test_category_types_summary_override` and asserts the override renders
      and is handed the same rows.
      **The red proof came out sharper than the task expected.** Creating the
      view without the package name does not ignore the override, it kills the
      render: `BackendViewFactory::create()` then falls back to the request's
      `route` attribute. Named `typo3/cms-backend` - the package the three
      removed partials registered against - the view searches core's template
      directory and nothing else and does not find the shipped template either
      (6 of 9 tests error with `InvalidTemplateResourceException`). So the
      package name is what puts this extension on the search path at all, and
      the override key is a consequence of that.
- [x] 1.3 Not in the original plan: `docs/architecture/page-module-category-summary.md`
      and the `For Developers` chapter `PageModuleSummary/` of
      `category_types`, plus a
      `Documentation/Changelog/3.0/Feature-PageModuleCategorySummary.rst`.
      Section 3.2 named only the three extensions, but `category_types` is
      where the new public API, the template and the override key live - an
      integrator reading only its manual would not find them otherwise.

## 2. Listeners per extension

- [x] 2.1 `EventListener\AddPageModuleCategorySummary` added to
      `academic_programs` (page type 20, group `programs`) with TYPO3's
      `#[AsEventListener]`.
      `Tests/Functional/Backend/PageModuleCategorySummaryTest` dispatches the
      real event through the container's dispatcher - so it covers the
      registration, not only the output - and asserts both category titles,
      the type labels, the not-set row, and an empty header for a standard
      page carrying the same two categories.
- [x] 2.2 The same for `academic_projects` (30, `projects`) and
      `academic_partners` (40, `partners`), each with its own test and
      fixture.
      **Red proof, with the count named:** removing `#[AsEventListener]` turns
      **three** of the four tests red in each extension. The fourth,
      `aStandardPageGetsNoSummaryAlthoughItCarriesTheSameCategories`, asserts
      an empty header - which an unregistered listener also produces. It is a
      guard against the opposite defect, not proof of this one.
- [x] 2.3 `PageModuleCategorySummaryIntegratorTypeTest` of `academic_programs`
      loads the new fixture extension `test_programs_extra_category_type`,
      which adds an `internship` type to the `programs` group with a
      **literal** title, and asserts that title and its category are rendered.
      Red proof: labelling from the *repaired* key convention
      `sys_category.<group>.<type>` - the alternative this change rejected -
      keeps every shipped-type test of all three extensions green and turns
      exactly this one test red. Without it the decision would be untested.
- [x] 2.4 The `templates.typo3/cms-backend.academic-*` line and its comment
      removed from the three `Configuration/page.tsconfig` files, the three
      `Resources/Private/Backend/Partials/PageLayout/Doktype*.html` partials
      deleted, and with them the whole `Resources/Private/Backend/` tree of
      each extension.
      **The first grep for this missed a second registration** and the review
      caught it: `academic-projects/ext_typoscript_setup.typoscript` registers
      the same tree through `module.tx_backend.view.partialRootPaths.ep`.
      `abdffb510` deleted that file from programs and partners in April 2025
      and skipped projects. It has been inert since TYPO3 v12.0 removed the
      mechanism (Breaking: #96812) - on branch `2` as much as here - but it
      named a directory this change deletes, so it goes in the projects commit.
      What the grep leaves now is historical mentions only: the 2.4 changelog
      of `academic_programs` and the docblocks of the new listeners.
- [x] 2.5 The class comment of `CategoryViewHelperTest` corrected, in the
      partners commit - the one that removes the last of the three partials,
      which is when the sentence becomes false. It said the removed partials
      were the view helper's only callers, and with them gone it has **no
      caller in this repository at all**. It says so now, and says the view
      helper stays public API for a project's own backend template - which is
      what these tests are the contract for. The consumer list of
      `docs/architecture/icons.md` is corrected in the same commit, for the
      same reason.
      Separately, in the **programs** commit, one line of
      `openspec/changes/ace-tbd-category-type-priority-order/design.md`: that
      unimplemented sibling change names `Doktype20.html`, which the programs
      commit deletes. The correction says what is actually true of the
      replacement - the summary takes its row order from the **registry**, not
      from `getAllCategoriesByType()` - so the next implementer looks in the
      right place.

## 3. Documentation

- [x] 3.1 `docs/architecture/page-module-category-summary.md` written and
      linked from `docs/architecture/Index.md`, in the page table and in the
      short-version list. There was no "architecture section on category
      types" to extend - `docs/architecture/` had no category types page at
      all - so this is a page of its own.
- [x] 3.2 `Documentation/Changelog/3.0/Important-PageModuleCategorySummary.rst`
      added to `academic_programs`, `academic_partners` and
      `academic_projects`: what the page module shows now, why it stopped
      showing it, the removed override key and the new one. Plus the `Feature-` entry
      of `category_types` from 1.3. Title 52 characters, over- and underlines
      measured against it; `checkRstRenderingAll` green.

## 4. File the issue

- [x] 4.1 Filed one ACE issue per commit in YouTrack: **ACE-687** (Story - the
      ACE project has no `Feature` type and `Story` is its convention for
      feature work, see ACE-584 and ACE-595) for the renderer, **ACE-688**,
      **ACE-689** and **ACE-690** (Bug) for programs, projects and partners.
      All four Version `2.4.0`, State `Open`, and all four linked `relates to`
      each other. The change was renamed to
      `ace-687-page-module-category-summary`.
      Committed as five commits, the archive included.
      **Deviation from the original wording**, which said all four are
      `[BUGFIX]`: the first commit adds public API to `category_types` and
      ships a `Feature-` changelog entry, so it is `[FEATURE]` with YouTrack
      type `Story` - the ACE project has no `Feature` type, as 4.1 records.
      The three that follow repair a summary that does not render - since
      March 2023 in `academic_programs`, and never in the other two, which
      were created from the already broken shape - and are `[BUGFIX]` with
      type `Bug`.

## 4b. What three review rounds were spent on

- [x] 4b.1 **One sentence, written once and copied three times, was wrong twice.**
      The first draft said the partials "never appeared". The log disproved it:
      `academic_programs` rendered the same markup as a full page module
      template override until `7b8eac8cf` (2023-03-17) renamed it to a partial
      nothing renders. The correction was then templated into all three
      extensions - and for `academic_projects` (`784742607`, 2023-09-19) and
      `academic_partners` (`bb4c01300`, 2025-02-25) *that* was wrong, because
      both were created as partials from the already broken shape and have
      never rendered. Two public changelogs carried a history of an extension
      that is not theirs.
      The fix was to stop sharing the sentence: each extension states its own
      history, and every statement about "the three partials" is phrased to
      hold for all three - none renders today, one used to.
      It also explained a leftover: `academic_projects` is the only one that
      still carried an `ext_typoscript_setup.typoscript`, because it was born
      with one.
- [x] 4b.2 **A bulk rewrap broke three shipped changelogs and no gate saw it.**
      Re-indenting bullet continuations to eight spaces under a four-space
      bullet makes a reST **definition list**, which is valid reST, so
      `checkRstRenderingAll` stayed green while the Impact section rendered as
      `<dt>`/`<dd>`. Found by rendering the HTML and reading it. After any
      reflow of reST, count `<dt>` in `Documentation-GENERATED-temp/`.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch 2 after a backport analysis
      (`docs/workflow/backporting.md`); the event exists on v12, the view
      creation differs there.

## 6. Definition of done

- [x] 6.1 Green for both: v13 `lintPhp`, `cgl -n`, `phpstan`, `unit` (829) and
      `functional` (2155); v14 the same after its own `composerUpdate`,
      `unit` (824) and `functional` (2170). The new tests also run green on
      **postgres** (v14), which is where a category query would show an
      unquoted value list.
- [x] 6.2 `lintMarkdown -n` (619 files, 0 problems) and
      `checkRstRenderingAll` (12 packages, 0) green.
- [x] 6.3 `docs/` and the extensions' `Documentation/` changelogs updated;
      `README.md` and `CONTRIBUTING.md` untouched - they only link.
- [x] 6.4 Archived as the last commit of the pull request.
