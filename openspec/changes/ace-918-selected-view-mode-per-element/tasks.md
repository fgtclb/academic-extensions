## 1. Tests first, red on the unchanged code

- [x] 1.1 `AcademicPersonsSelectedViewModePerElementTest` of `academic_persons`
  with the fixture
  `Plugins/Fixtures/AcademicPersonsSelectedViewModePerElement/records.csv`:
  two selected profiles and two selected contracts elements on a page each,
  both with the switch, a page with one element of each type, and both pages
  translated to German. Choosing the table in one element, a translated page
  with the fallback types `strict` and `free`, and a link of the default
  language opened on the translated page. Run on TYPO3 v13, then v14, against
  the code of `main`: 6 of 7 red on both, by the second element rendering a
  table as well (`.agent/tmp/ace-918/logs/red-core13.log`,
  `red-core14.log`). The page with one element of each type is a guard that
  passes before and after.
- [x] 1.2 `aModeThatNamesNoElementOfThePageIsIgnored`: a plain mode without an
  element and a mode keyed by an element not on the page change no element.
  Red on v13 and v14 against the code of `main` for the plain mode, which
  switched both elements (`red-core13-ignored.log`, `red-core14-ignored.log`).
  The key of an element not on the page is a guard.

## 2. Implementation

- [x] 2.1 The selected profiles and selected contracts actions read the mode
  from the key of their default-language uid and assign `viewModeArguments`,
  verified by the tests of 1.1 and 1.2 on v13 and v14.
- [x] 2.2 `Partials/Profile/ViewMode/Switch.html` builds the links from
  `viewModeArguments` when the selected templates pass it, the list branch
  unchanged, verified by the tests of 1.1 and every plugin test of
  `academic_persons` on v13 and v14.
- [x] 2.3 `AcademicPersonsListViewModesTest` reads and sends the mode of the
  selected elements under the uid of their element. Only its two selected
  data sets change.
- [x] 2.4 `LegacyDeliveryTest` of `packages-dev/dev-site` masks the uid that
  keys the view mode argument, since the mirrored elements of the `/legacy/`
  tree have uids of their own. It failed for the two selected pages in both
  languages in the first v14 gate run
  (`.agent/tmp/ace-918/logs/gate-functional-14.log`) and passes with the rule
  on v13 and v14.

## 3. Documentation

- [x] 3.1 The manual pages of both elements describe the argument instead of
  the note that two elements share the mode. The template documentation and
  the plugin view event page name `viewModeArguments`.
- [x] 3.2 `docs/architecture/list-filter-urls.md` gets a section on the view
  mode of the selected elements, `docs/testing/seed-verification.md` names the
  new normalisation.
- [x] 3.3 No changelog entry: `Feature-ListViewModes.rst` of 3.0 is unreleased
  and does not name the shape of the argument, and the per-element mode adds
  nothing an integrator has to act on.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_persons`
  manual, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-918`, and
  the archive of this change with the spec sync as the last commit.
