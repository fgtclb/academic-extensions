## 1. Tests first

- [x] 1.1 Add a case to `Tests/Functional/Pages/AcademicProgramPageTemplateTest.php`
  in `academic-programs` whose setup omits
  `Configuration/TypoScript/ContentLoad/setup.typoscript`, with one visible
  `tt_content` record in colPos 0: assert HTTP 200 and the record's content;
  record that it fails on the unchanged code with the `f:cObject` exception.
- [x] 1.2 Add fixtures for a second colPos 0 record (manual order), a colPos 1
  record and a translated page with translated content; assert the order, the
  absence of the colPos 1 record and the translated content, and record which
  assertions fail on the unchanged code.
- [x] 1.3 Add a case with a `PAGEVIEW` fixture page object and assert the same
  content, so both integrations are covered.
- [x] 1.3a Add two pairs of main column elements, each sharing a `sorting`
  value, one written in ascending and one in descending uid order, and
  assert uid order. Measured without the `uid` tiebreaker on v13 and v14:
  PostgreSQL 10 returns the ties in reverse write order (the ascending pair
  fails), PostgreSQL 16 in write order (the descending pair fails), SQLite
  passes; one pair alone would be a guard on one of the two versions.
- [x] 1.4 Change `Tests/Functional/SiteSet/SiteSetDeliveryTest.php` to assert
  that `fgtclb/academic-programs-content-load` is not registered and that a
  site on the aggregate set has no `styles.content.getContent`, and
  `Tests/Functional/Tca/StaticRegistrationTest.php` to assert that the
  content load static template is not registered; record that all three
  assertions fail on the unchanged code.

## 2. Implementation

- [x] 2.1 Add `page.10.variables.programContent` (`CONTENT` on `tt_content`,
  colPos 0, `orderBy = sorting, uid`) inside the doktype 20 condition of
  `Configuration/TypoScript/Page/AcademicPrograms.typoscript`.
- [x] 2.2 Replace the `f:cObject` call in
  `Resources/Private/Pages/AcademicProgram.html` with
  `{programContent -> f:format.raw()}` and verify 1.1 to 1.3 pass.
- [x] 2.3 Drop the explicit content-load include from the existing page test
  and verify it still passes.
- [x] 2.4 Revert 2.2, watch 1.1 go red, restore it.
- [x] 2.5 Remove `Configuration/Sets/ContentLoad/`,
  `Configuration/TypoScript/ContentLoad/`, the dependency on
  `fgtclb/academic-programs-content-load` in
  `Configuration/Sets/Full/config.yaml` and the `addStaticFile()` call of
  the content load override in `Configuration/TCA/Overrides/sys_template.php`;
  adjust the comments there that mention the override. Verify 1.4 passes and
  grep that `academic_programs` has no reference to the set or the folder
  left.
- [x] 2.6 Adjust the comment on the three content-load sets in
  `core-13/config/sites/academics/config.yaml` and
  `core-14/config/sites/academics/config.yaml`, which calls leaving one out a
  fatal error.

## 2a. The upgrade check (added after the premise on unknown sets failed)

- [x] 2a.1 Add `ConfigurationFindingKind::UnavailableSet` (`unavailable-set`)
  and report it from `ConfigurationChecker::checkSites()` as an error: a
  declared set `SetRegistry::hasSet()` does not know when either end of its
  `missingDependency` chain is academic (an academic set that is missing,
  invalid or misses a set of another vendor; a site package set that misses
  an academic one); an unavailable alias set gets the error instead of the
  alias notice; a constant maps `fgtclb/academic-programs-content-load` to
  what replaced it.
- [x] 2a.2 Tests in `ConfigurationCheckerSiteTest` (removed and uninstalled
  academic sets, a site package set missing one directly and through another
  set, the vendor rule, an alias set of an extension that is not installed
  reported as an error and not as a notice) and
  `UpgradeCheckCommandConfigurationTest` (the printed line and the exit
  status); both alias notice tests now load `academic_study_plan`, whose
  alias they name, because a site on an alias whose extension is not
  installed is unavailable. Eleven mutations of both parts, each caught; the
  `uid` tiebreaker was measured by hand (task 1.3a).
- [x] 2a.3 The command help text, `docs/architecture/upgrade-checks.md`,
  `Documentation/UpgradeCheck/Index.rst` and
  `Documentation/Changelog/3.0/Feature-UpgradeCheckUnavailableSets.rst` of
  `academic_base`.

## 3. Documentation

- [x] 3.1 Replace the content load sections of
  `Documentation/Configuration/Index.rst` (the set table row, the section
  "The content load override" and the static template row): the page type
  needs no set, the program page content is
  `page.10.variables.programContent`, and the override no longer exists.
- [x] 3.2 Add
  `Documentation/Changelog/3.0/Breaking-ContentLoadSetRemoved.rst` from
  `Build/Documentation/Templates/Changelog-Breaking.rst`: the removed set,
  static template and aggregate dependency; the HTTP 500 of a site whose
  configuration still names the set; the migration (remove the set or static
  template entry, switch a customised or overridden
  `styles.content.getContent` to `page.10.variables.programContent`, or
  define `styles.content.getContent` in the site package when a site template
  keeps rendering it). Check the reST over/underline lengths.
- [x] 3.3 Update the `docs/` page that describes the site sets, if it names
  the page type dependency or the content-load sets.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack and rename the
  change to `ace-<NNN>-program-page-content-without-getcontent` - ACE-721.
- [x] 4.2 Commit in TYPO3 Core format, `[!!!][FEATURE] ACE-<NNN>: <subject>`.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [x] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `Documentation/` changelog updated in the same
  change.
- [x] 5.5 Archive the change as the last commit of the pull request.

## 6. Backport (a pull request of its own)

Branch `2` gets the non-breaking part as a change of its own under the same
name, in its own pull request: the `programContent` variable and the template
switch; the content-load set and static template stay, with an `Important`
changelog entry and no upgrade check finding (branch `2` removes no set).
