## 1. Tests first, red on the unchanged code

- [x] 1.1 `AcademicContacts4PagesDetailLinkTest` of `academic_contacts4pages`
  with the fixture
  `Plugins/Fixtures/AcademicContacts4PagesDetailLink/records.csv`: a contact
  with a role and one without on one page, rendered by a site that selects
  the static template of the contact list alone and by a site that uses the
  site sets of both extensions, each with the default and with "no link".
  Run on TYPO3 v13, then v14, against the code of `main`: the two "no link"
  tests red on both by a linked name, the two default tests green
  (`.agent/tmp/ace-921/logs/red-13-detaillink.log`,
  `red-14-detaillink.log`). A fifth case, the TypoScript setup of the site
  setting the plugin setting back to "link" while the constant says "no
  link", is a guard for the override the manual shows: it passes before and
  after, and differs from the red static "no link" case by that line only.
- [x] 1.2 `listPluginLinksEachContactWhileThePersonsElementsShowNamesWithoutLink`
  of `AcademicContacts4PagesListPluginTest` pinned the old behaviour. It
  becomes `listPluginShowsTheNamesWithoutLinkWhenThePersonsSettingSaysNone`,
  red on v13 and v14 against the code of `main`
  (`red-13-listplugin.log`, `red-14-listplugin.log`).

## 2. Implementation

- [x] 2.1 `Configuration/TypoScript/List/setup.typoscript` maps
  `detailLink = {$plugin.tx_academicpersons.detailLink}` next to `detailPid`,
  verified by the tests of 1.1 and 1.2 and every functional test of
  `academic_contacts4pages` on v13 and v14.

## 3. Documentation

- [x] 3.1 The contact list chapter of the `academic_contacts4pages` manual
  says that the names follow the persons setting instead of that they are
  always linked, and shows the TypoScript that keeps the links for the
  contact list alone. The configuration chapter adds `detailLink` to the
  mapped constants and says which settings a page template passes.
- [x] 3.2 The configuration chapter of the `academic_persons` manual and its
  unreleased 3.0 entry `Feature-ProfileItemsWithoutDetailLink.rst` name the
  contact list as following the setting instead of linking.
- [x] 3.3 `docs/architecture/shared-partials.md` and
  `docs/architecture/typoscript-and-site-sets.md` no longer name the contact
  list as the caller that keeps linking.
- [x] 3.4 The unreleased 3.0 `Feature` entry of the setting is corrected, and
  `academic_contacts4pages` gets the 3.0 entry
  `Important-ContactsFollowTheDetailLinkSetting.rst`: it names the setting,
  its default `link`, that `none` removes the links of the contact list as
  well, and the TypoScript setup that keeps them, as
  `Important-ContactsFollowTheProfileImageSettings.rst` does for the image
  settings.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_contacts4pages`
  and `academic_persons` manuals, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-921`, and
  the archive of this change with the spec sync as the last commit.
