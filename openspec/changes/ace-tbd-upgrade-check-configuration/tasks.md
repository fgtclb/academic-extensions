## 1. Checker service

- [ ] 1.1 Add the finding value object and the stateless checker with the
  static template check; functional test with a fixture `sys_template` row
  holding one registered and one dead academic path asserts exactly one
  warning naming the dead path, and is shown to fail with the check removed.
  The dead path is not one of the four 2.x paths that
  `ace-tbd-legacy-typoscript-paths` keeps; once that change has landed, a
  row holding one of them is added and asserted to report nothing.
- [ ] 1.2 Add the page TSconfig check for the `@import` form, the
  `<INCLUDE_TYPOSCRIPT:` form and `tsconfig_includes`; functional test with
  one resolving and one dead reference per form, shown to fail with the check
  removed.
- [ ] 1.3 Verify whether TYPO3 v13 and v14 both read a site-level
  `page.tsconfig`; include it in the TSconfig check only if both do, and
  record the result in `design.md`.
- [ ] 1.4 Add the alias set notice; functional test with a fixture site
  depending on `fgtclb/academic-persons-default`, shown to fail without the
  check.
- [ ] 1.5 Add the set plus static template check with the set-to-extension
  map built from the active academic packages; functional test with one
  conflicting and one non-conflicting site, shown to fail without the check.
- [ ] 1.6 Add the XCLASS check; test registering an XCLASS for a final and for
  a non-final academic class asserts one error and one warning, shown to fail
  without the check.
- [ ] 1.7 Functional test that a run with findings leaves every fixture record
  unchanged (assert against the imported CSV), shown to fail by letting the
  test write one row on purpose.

## 2. Status report

- [ ] 2.1 Add the status provider, exclude `Classes/Report/` from the resource
  load of `academic-base/Configuration/Services.yaml`, and register the
  provider through a compiler pass in a new
  `academic-base/Configuration/Services.php` modelled on
  `academic-persons/Configuration/Services.php`.
- [ ] 2.2 Functional test modelled on
  `academic-persons/Tests/Functional/Report/LegacySettingsStatusTest.php`:
  one status per finding, a single OK status without findings, and no
  provider in the container without EXT:reports; the last assertion is shown
  to fail when the pass registers the provider unconditionally.

## 3. Command check group

- [ ] 3.1 Once `ace-tbd-upgrade-check-template-overrides` is merged, add the
  configuration group to `academic:upgrade:check`; functional command test
  asserts a non-zero exit status for a warning and status zero for a notice
  only, shown to fail when the exit status ignores the findings.

## 4. Documentation

- [ ] 4.1 Document the check in `academic-base/Documentation/` (a page linked
  from its `Index.rst`), with one paragraph per finding and how to fix it.
- [ ] 4.2 Add `academic-base/Documentation/Changelog/3.0/Feature-UpgradeConfigurationCheck.rst`
  from `Build/Documentation/Templates/Changelog-Feature.rst`.
- [ ] 4.3 Update the double-parse section of
  `docs/architecture/typoscript-and-site-sets.md` to name the check as the
  detection of a set combined with a static template.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-upgrade-check-configuration`, and commit in TYPO3 Core
  format as `[FEATURE] ACE-<NNN>: Report stale 2.x configuration`.

## 6. Definition of done

- [ ] 6.1 `lintPhp` green.
- [ ] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 6.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`. The check writes nothing, so a PostgreSQL
  run is not required.
- [ ] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.5 `docs/` and the `Documentation/Changelog/3.0/` entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 6.6 Archive the change as the last commit of the pull request.
