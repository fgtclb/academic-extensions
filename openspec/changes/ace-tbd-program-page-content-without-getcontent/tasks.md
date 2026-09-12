## 1. Tests first

- [ ] 1.1 Add a case to `Tests/Functional/Pages/AcademicProgramPageTemplateTest.php`
  in `academic-programs` whose setup omits
  `Configuration/TypoScript/ContentLoad/setup.typoscript`, with one visible
  `tt_content` record in colPos 0: assert HTTP 200 and the record's content;
  record that it fails on the unchanged code with the `f:cObject` exception.
- [ ] 1.2 Add fixtures for a second colPos 0 record (manual order), a colPos 1
  record and a translated page with translated content; assert the order, the
  absence of the colPos 1 record and the translated content, and record which
  assertions fail on the unchanged code.
- [ ] 1.3 Add a case with a `PAGEVIEW` fixture page object and assert the same
  content, so both integrations are covered.
- [ ] 1.4 Change `Tests/Functional/SiteSet/SiteSetDeliveryTest.php` to assert
  that `fgtclb/academic-programs-content-load` is not registered and that a
  site on the aggregate set has no `styles.content.getContent`, and
  `Tests/Functional/Tca/StaticRegistrationTest.php` to assert that the
  content load static template is not registered; record that all three
  assertions fail on the unchanged code.

## 2. Implementation

- [ ] 2.1 Add `page.10.variables.programContent` (`CONTENT` on `tt_content`,
  colPos 0, `orderBy = sorting, uid`) inside the doktype 20 condition of
  `Configuration/TypoScript/Page/AcademicPrograms.typoscript`.
- [ ] 2.2 Replace the `f:cObject` call in
  `Resources/Private/Pages/AcademicProgram.html` with
  `{programContent -> f:format.raw()}` and verify 1.1 to 1.3 pass.
- [ ] 2.3 Drop the explicit content-load include from the existing page test
  and verify it still passes.
- [ ] 2.4 Revert 2.2, watch 1.1 go red, restore it.
- [ ] 2.5 Remove `Configuration/Sets/ContentLoad/`,
  `Configuration/TypoScript/ContentLoad/`, the dependency on
  `fgtclb/academic-programs-content-load` in
  `Configuration/Sets/Full/config.yaml` and the `addStaticFile()` call of
  the content load override in `Configuration/TCA/Overrides/sys_template.php`;
  adjust the comments there that mention the override. Verify 1.4 passes and
  grep that `academic_programs` has no reference to the set or the folder
  left.
- [ ] 2.6 Adjust the comment on the three content-load sets in
  `core-13/config/sites/academics/config.yaml` and
  `core-14/config/sites/academics/config.yaml`, which calls leaving one out a
  fatal error.

## 3. Documentation

- [ ] 3.1 Replace the content load sections of
  `Documentation/Configuration/Index.rst` (the set table row, the section
  "The content load override" and the static template row): the page type
  needs no set, the program page content is
  `page.10.variables.programContent`, and the override no longer exists.
- [ ] 3.2 Add
  `Documentation/Changelog/3.0/Breaking-ContentLoadSetRemoved.rst` from
  `Build/Documentation/Templates/Changelog-Breaking.rst`: the removed set,
  static template and aggregate dependency; the silent drop of an unknown set
  name from a site configuration; the migration (remove the set or static
  template entry, switch a customised or overridden
  `styles.content.getContent` to `page.10.variables.programContent`, or
  define `styles.content.getContent` in the site package when a site template
  keeps rendering it). Check the reST over/underline lengths.
- [ ] 3.3 Update the `docs/` page that describes the site sets, if it names
  the page type dependency or the content-load sets.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack and rename the
  change to `ace-<NNN>-program-page-content-without-getcontent`.
- [ ] 4.2 Commit in TYPO3 Core format, `[!!!][BUGFIX] ACE-<NNN>: <subject>`.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the `Documentation/` changelog updated in the same
  change.
- [ ] 5.5 Archive the change as the last commit of the pull request.
