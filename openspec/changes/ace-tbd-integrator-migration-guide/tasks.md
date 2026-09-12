## 1. Verify before writing

- [ ] 1.1 In the `core-13` instance, remove `special.skipSync` and send a
  `skipSync` update to the endpoint; expect 422 `invalid_profile_data`. If it
  writes, stop and file a bugfix instead of documenting the recipe.
- [ ] 1.2 List which of the changes named in the design have landed, and mark
  every chapter that depends on one that has not.

## 2. Entry point

- [ ] 2.1 Add `academic-base/Documentation/Upgrade/Index.rst` with the thirteen
  steps, each linking its owning changelog, and add it to the toctree and the
  card grid of `academic-base/Documentation/Index.rst`. Step 5 names the
  four 2.x static template paths as deprecated until 4.0 and points to "All
  components" or the site set. Step 11 documents the removal of the three
  content-load sets, not a deprecation: what they defined, the
  `styles.content.getContent` definition for site templates that still
  render it, and links to the `Breaking-` entries of the owning changes:
  `ace-tbd-program-page-content-without-getcontent` (programs set),
  `ace-tbd-program-facts-field-list` (`Partials/Program/Categories.html`) and
  `ace-tbd-page-templates-sections-subtitle` (partners and projects sets).
- [ ] 2.2 Link the guide from the index of every other manual
  (`academic-bite-jobs`, `academic-contact4pages`, `academic-jobs`,
  `academic-partners`, `academic-persons`, `academic-persons-edit`,
  `academic-persons-sync`, `academic-programs`, `academic-projects`,
  `academic-study-plan`, `typo3-category-types`).

## 3. Extension chapters

- [ ] 3.1 Add `academic-persons-edit/Documentation/Upgrade/Index.rst`: the
  intent-to-setting table, the gaps and the per-path checklist; link it from
  step 6 of the persons upgrade page.
- [ ] 3.2 Add `academic-programs/Documentation/Upgrade/Index.rst`: template
  naming and include order, the removed `Partials/Program/Categories.html`
  and its replacement by the facts partial, unit labels by
  `locallangXMLOverride`, adopting the finder (and the element one project
  built for it), the application link data.
- [ ] 3.3 Extend the "Affected Installations" section of
  `Important-PageTemplateNameIsSetExplicitly.rst` in programs, partners and
  projects with the include-order explanation (ACE-601), instead of a new
  changelog entry.
- [ ] 3.4 Add `academic-study-plan/Documentation/Upgrade/Index.rst`: decimal
  credit points, partials and data attributes, asset switches, and moving
  container-based semester plans.
- [ ] 3.5 Add the category type rename with the SQL statements to
  `typo3-category-types/Documentation/`.

## 4. Documentation gate and docs/

- [ ] 4.1 Run `checkRstRenderingAll`; prove the gate catches a broken link by
  misspelling one `:ref:` label once and watching the run fail, then restore.
- [ ] 4.2 Name the guide in `docs/workflow/changelog-and-documentation.md` as
  the place a new breaking change adds its step; verify with
  `lintMarkdown -n`.
- [ ] 4.3 State in the pull request that no changelog entry is added, because
  the guide changes no behaviour.

## 5. File the issue

- [ ] 5.1 After writing, file the ACE issue in YouTrack, relate it to ACE-367,
  ACE-450 and ACE-601, and verify the key.
- [ ] 5.2 Rename the change to `ace-<NNN>-integrator-migration-guide`.
- [ ] 5.3 Commit as `[DOCS] ACE-<NNN>: Add the 2.x to 3.0 integrator guide`
  in TYPO3 Core format.

## 6. Backport

- [ ] 6.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`), for the parts of the programs and
  category types chapters that apply to 2.x (template naming, unit labels,
  category type rename, legacy migrations). Decimal credit points ship on
  `main` only and are not mentioned in the branch `2` guide.

## 7. Definition of done

- [ ] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 7.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` is updated; `README.md` and `CONTRIBUTING.md` still only
  summarize.
- [ ] 7.5 Archive the change as the last commit of the pull request.
