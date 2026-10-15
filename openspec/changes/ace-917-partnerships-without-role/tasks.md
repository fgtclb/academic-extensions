## 1. Tests first, red on the unchanged code

- [x] 1.1 `PartnershipsWithoutRoleTest` of `academic_partners` with the fixture
  `Plugins/Fixtures/PartnershipsWithoutRole/partnershipsPages.csv`, both taken
  from `main` with the reading of the item lists adapted to the markup of this
  branch: a page mixing partnerships with and without a role, arranged out of
  uid order, in English and in German with both fallback types, a page without
  roles and a page with roles only. The first version of the test, five tests
  without page 6 and without the heading elements, ran on TYPO3 v12, then
  v13, against the code of `2`: 4 of 5 red on both, by the missing row after
  the role groups and the missing processor variable. The page with roles
  only is a guard. Logs `.agent/tmp/ace-917-2/logs/red-core12.log` and
  `red-core13.log`. The committed test is proven by 1.3 and 1.4.
- [x] 1.2 With the fix applied and only the teaser template of `2` restored,
  the three mixed page tests of that first version failed on the teaser on
  v12 and v13 (`red-core12-teaser-only.log`, `red-core13-teaser-only.log`).
  For the committed test the teaser assertions are proven by the teaser run
  of 1.4.
- [x] 1.3 Page 6 of the fixture, taken from `main` as well, holds a
  partnership with a hidden role, one with a deleted role and a hidden
  partnership with the hidden role. The test
  `partnershipsWithAnUnavailableRoleRenderAfterTheRoleGroups` was red on v12
  and v13 against the code of `2`, the first two being left out
  (`r2-red-core12.log`, `r2-red-core13.log`). With the fix both render after
  the role groups and the hidden partnership stays out, on v12 and v13
  (`r2-green-core12.log`, `r2-green-core13.log`), so a hidden or deleted role
  resolves to no role on both.
- [x] 1.4 The item lists carry the heading element of the partner names:
  `h3` in a role group, `h2` without a role. With `grouped: 'true'` added to
  the row without a role, four tests failed by `h3` on v12 and v13, once for
  the list template and once for the teaser template
  (`r2-red-core12-grouped-*.log`, `r2-red-core13-grouped-*.log`).

## 2. Implementation

- [x] 2.1 `PartnershipRoles::withoutRole()` returns the partnerships without a
  role in the given order, the ACE-430 `collect()` stays as it is, verified by
  the tests of 1.1 and by `PartnershipsPerLanguageTest`.
- [x] 2.2 Both plugin actions and `PartnershipProcessor` assign
  `partnershipsWithoutRole`, verified by the tests of 1.1 and 1.3.
- [x] 2.3 `Partner/PartnershipsList.html` and `Partner/PartnershipsTeaser.html`
  render the list as a row after the role groups without heading and without
  `grouped`, the flat list branch unchanged, verified by the tests of 1.1 and
  1.3 and every plugin test of the extension on v12 and v13.

## 3. Documentation

- [x] 3.1 `Changelog/2.4/Important-PartnershipsWithoutARoleAreListed.rst` and
  `Upgrade/From23To24.rst` copied from `main` byte for byte, identical by
  `cmp` to `fc0f10aed` there, and every reference of both resolving in the
  rendering of the `academic_partners` manual. The statements of the entry
  on hidden and deleted roles hold on v12 by the tests of 1.3.
- [x] 3.2 `docs/architecture/translation-synchronization.md` names the list
  of partnerships without a role, those with a hidden or deleted role
  included, after its paragraph on the role grouping of ACE-430, verified by
  `lintMarkdown -n`. No manual page changes: this branch has no integrator or
  styling chapter that warned about the defect.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_partners`
  manual, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-917`, and
  the archive of this change with the spec sync as the last commit.
