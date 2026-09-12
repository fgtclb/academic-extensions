## 1. Interface and class

- [ ] 1.1 Add a unit test asserting that a persons context instance is an
  instance of the `academic_base` interface; run it on main and record the
  failure.
- [ ] 1.2 Let the persons interface extend the base interface, mark it
  `@deprecated` for 4.0, and add `getContentObjectRenderer()` to the persons
  context class; the test from 1.1 turns green.
- [ ] 1.3 Unit test that the persons context returns the request's current
  content object, and `null` when the request carries none; shown red by
  returning `null` unconditionally.
- [ ] 1.4 Functional test with a fixture listener typed against the base
  interface for the persons list event, and one typed against the persons
  interface for the detail event; both are called. The first is shown red by
  reverting the `extends`.

## 2. Documentation

- [ ] 2.1 Add `academic-persons/Documentation/Changelog/3.0/Breaking-PluginControllerActionContextInterfaceExtendsBase.rst`
  (implementers add one method) and
  `Deprecation-PersonsPluginControllerActionContextInterface.rst` (removal in
  4.0, type against the base interface), from the templates in
  `Build/Documentation/Templates/`.
- [ ] 2.2 Update `academic-persons/Documentation/Developers/Index.rst` to
  recommend the base interface in listener examples. The extension point
  section of `docs/architecture/class-design.md` is written afterwards by
  `ace-tbd-extension-point-policy`, which lands after this change.

## 3. File the issue

- [ ] 3.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-single-action-context-interface`, and commit in TYPO3
  Core format as `[!!!][TASK] ACE-<NNN>: Unify the plugin action context`.

## 4. Definition of done

- [ ] 4.1 `lintPhp` green.
- [ ] 4.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 4.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`. Nothing is written, so a PostgreSQL run is
  not required.
- [ ] 4.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 4.5 `docs/` and the `Documentation/Changelog/3.0/` entries are part of
  the change; `README.md` and `CONTRIBUTING.md` still only summarize and
  link.
- [ ] 4.6 Archive the change as the last commit of the pull request.
