## 1. Tests first, red on the unchanged code

- [x] 1.1 `PartnershipsWithoutRoleTest` of `academic_partners` with the fixture
  `Plugins/Fixtures/PartnershipsWithoutRole/partnershipsPages.csv`: a page
  mixing partnerships with and without a role, arranged out of uid order, in
  English and in German with both fallback types, a page without roles and a
  page with roles only. It reads the item lists of the list and the teaser,
  each with its heading, and the partnerships without a role the page data
  processor hands to the test TypoScript. Run on TYPO3 v13, then v14, against
  the code of `main`: 4 of 5 red on both, by the missing list after the role
  groups and the missing processor variable. The page with roles only is a
  guard. Logs `.agent/tmp/ace-917/logs/red-core13.log` and `red-core14.log`.
- [x] 1.2 With the fix applied and only the teaser template of `main` restored,
  the three mixed page tests fail on the teaser on v13
  (`red-core13-teaser-only.log`), so the teaser assertions are proven too.

- [x] 1.3 Page 6 of the fixture holds a partnership with a hidden role, one
  with a deleted role and a hidden partnership with the hidden role. The test
  `partnershipsWithAnUnavailableRoleRenderAfterTheRoleGroups` was red on v13
  against the code of `main`, the first two being left out
  (`r2-red-core13-unavailable-role.log`), and the hidden partnership stays
  out with the fix.
- [x] 1.4 The item lists carry the heading element of the partner names:
  `h3` in a role group, `h2` without a role. With `grouped: 'true'` added to
  the block without a role in the list template, four tests failed on v13 by
  `h3` (`r2-red-core13-grouped.log`).

## 2. Implementation

- [x] 2.1 `PartnershipRoles::withoutRole()` returns the partnerships without a
  role in the given order, the ACE-430 `collect()` stays as it is, verified by
  the tests of 1.1 and by `PartnershipsPerLanguageTest`.
- [x] 2.2 Both plugin actions and `PartnershipProcessor` assign
  `partnershipsWithoutRole`, verified by the tests of 1.1.
- [x] 2.3 `Partner/PartnershipsList.html` and `Partner/PartnershipsTeaser.html`
  render the list after the role groups without heading and without
  `grouped`, the flat list branch unchanged, verified by the tests of 1.1 and
  every plugin test of the extension on v13 and v14.

## 3. Documentation

- [x] 3.1 The manual pages of both elements describe the partners without a
  role instead of the warning, the styling chapter outlines the markup and
  names the three template variables, the plugin view event page names the
  new variable.
- [x] 3.2 `Changelog/2.4/Important-PartnershipsWithoutARoleAreListed.rst`,
  written so that branch `2` can take it byte for byte: no element title, no
  markup of one branch, references that exist on both.
- [x] 3.3 The upgrade guides from 2.3 to 2.4 and from 2.3 to 3.0 list the new
  variable for both templates and a check after the update.
  `docs/architecture/translation-synchronization.md` names the list next to
  the role handling of ACE-430.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_partners`
  manual, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-917`, and
  the archive of this change with the spec sync as the last commit.
