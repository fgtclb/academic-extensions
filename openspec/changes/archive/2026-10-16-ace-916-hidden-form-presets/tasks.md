## 1. Tests first, red on the unchanged code

- [x] 1.1 `AcademicPartnersHiddenFormPartsTest`: one test per scenario of the
  partners delta spec, the redirect followed, and the page links of a
  paginated list. Run on TYPO3 v13 and v14 against the factory of `main`:
  8 of 10 red on both, by the redirect target or the rendered partners. The
  two green ones are guards: a hidden filter without defaults is filtered by a
  link, and "All" clears a default of an offered type. Logs
  `.agent/tmp/ace-916/logs/rework-red-main-core13-partners.log` and
  `rework-red-main-core14-partners.log`.
- [x] 1.2 `AcademicProjectsHiddenFormPartsTest`: the same for the projects
  delta spec, the active state and the selected projects element included:
  10 of 12 red on v13 and v14 against `main`, the same two guards green
  (`rework-red-main-core13-projects.log`, `rework-red-main-core14-projects.log`).
- [x] 1.3 `AcademicProgramsHiddenFormPartsTest`: the same for the programs
  delta spec and the new scenarios of the program finder spec: 6 of 9 red on
  v13 and v14 against `main` (`rework-red-main-core13-programs.log`,
  `rework-red-main-core14-programs.log`). The three green ones are guards of
  what already held: the hidden sorting with the filter form (ACE-91), a
  finder selection of a type the list presets, and "All" clearing a preset of
  the list's own filter. Two later tests, a linked sorting against a hidden
  sorting and a link on a hidden filter without defaults, run the code path
  partners and projects prove. They failed on v14 with the hidden sorting rule
  and the narrowing broken on purpose (`nits-broken-core14-programs.log`).
- [x] 1.4 The narrowing of a hidden filter and the per type rule replaced a
  first implementation that took a hidden partner or project filter from the
  settings alone and kept no defaults per type. Against that one, the
  narrowing, the link on a hidden filter without defaults and the per type
  test were red on v13 and v14 for partners and projects, 3 each
  (`rework-red-core13-*.log`, `rework-red-core14-*.log`).
- [x] 1.5 `CategoryFilterNormalizerTest` covers `keepsPresets()` and
  `toUidListKeepingPresets()`, which do not exist on `main`.

## 2. Implementation

- [x] 2.1 `CategoryFilterNormalizer::keepsPresets()` and
  `toUidListKeepingPresets()` in `category_types`, verified by 1.5.
- [x] 2.2 Partners factory: the sorting of a hidden part from the settings,
  the categories through the normalizer, verified by the tests of 1.1.
- [x] 2.3 Projects factory: the same, and the active state, verified by the
  tests of 1.2.
- [x] 2.4 Programs factory: the same, verified by the tests of 1.3.
- [x] 2.5 Every existing test of the three extensions stays green, verified
  by the functional runs of 4.1, the active filter fixtures unchanged.

## 3. Documentation

- [x] 3.1 Manuals: the list and map pages of `academic_partners`, the list and
  finder pages of `academic_programs`, the list page of `academic_projects`,
  whose selected projects page refers to it. Each list page names the limit
  of two lists of one plugin on a page.
- [x] 3.2 A `Changelog/2.4/Important-HiddenFormPartsKeepTheirPresets.rst` per
  extension, written so that branch `2` can take it byte for byte, a
  `Changelog/3.0/Important-*.rst` per extension for the per type rule and the
  program finder, and the 3.0 entries `Feature-FilterSelectionsHaveAUrl.rst`
  made true again.
- [x] 3.3 The upgrade guides from 2.3 and 2.4 of the three extensions, and
  `docs/architecture/list-filter-urls.md` and `list-filter-types.md`.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the three manuals, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-916`, and
  the archive of this change with the spec sync as the last commit.
