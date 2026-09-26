## 1. Interface and class

- [x] 1.1 Add a unit test asserting that a persons context instance is an
  instance of the `academic_base` interface; run it on main and record the
  failure.
- [x] 1.2 Let the persons interface extend the base interface, mark it
  `@deprecated` for 4.0, and add `getContentObjectRenderer()` to the persons
  context class; the test from 1.1 turns green.
- [x] 1.3 Unit test that the persons context returns the request's current
  content object, and `null` when the request carries none; shown red by
  returning `null` unconditionally.
- [x] 1.4 Unit tests that both context classes return `null` for a foreign
  value under the content object attribute; shown red without the type check.
- [x] 1.5 Functional test with a fixture listener typed against the base
  interface for the persons list event, and one typed against the persons
  interface for the detail event; both are called, and the listener of the
  profile title placeholder event receives the detail plugin's content
  element. The first is shown red by reverting the `extends`.
- [x] 1.6 Build one persons context per action in the persons controller and
  hand it to the repository and the event; drop the `@todo` of ACE-715.

## 2. Documentation

- [x] 2.1 Add `academic-persons/Documentation/Changelog/3.0/Breaking-PluginControllerActionContextInterfaceExtendsBase.rst`
  (implementers add one method) and
  `Deprecation-PersonsPluginControllerActionContext.rst` (removal in
  4.0, type against the base interface), and
  `academic-base/Documentation/Changelog/3.0/Important-PluginControllerActionContextContentObject.rst`
  (the accessor answers `null` for a foreign value), from the templates in
  `Build/Documentation/Templates/`.
- [x] 2.2 Update `academic-persons/Documentation/Developers/Index.rst` to
  recommend the base interface in listener examples. The extension point
  section of `docs/architecture/class-design.md` is written afterwards by
  `ace-tbd-extension-point-policy`, which lands after this change.

## 3. The issue

- [x] 3.1 The change implements ACE-442, retagged `[3.x]` with version
  3.0.0; the removal in 4.0 is ACE-747, the application type getter ACE-748.
  Commit in TYPO3 Core format as
  `[!!!][TASK] ACE-442: Unify the plugin action context`.

## 4. Definition of done

- [x] 4.1 `lintPhp` green.
- [x] 4.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [x] 4.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`. Nothing is written, so a PostgreSQL run is
  not required.
- [x] 4.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.5 `docs/` and the `Documentation/Changelog/3.0/` entries are part of
  the change; `README.md` and `CONTRIBUTING.md` still only summarize and
  link.
- [x] 4.6 Archive the change as the last commit of the pull request.
