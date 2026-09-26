## 1. Architecture test

- [x] 1.1 Add the event class test to `packages-dev/monorepo-shared`,
  asserting that every event class under `packages/fgtclb/*/Classes/` is
  `final` and instantiated by another production file; verify it finds all 19
  current event classes.
- [x] 1.2 Add the test that the `@api` tags and the `\FGTCLB\…` class names of
  the page are the same set.
- [x] 1.3 Add the test that every class implementing Extbase's
  `DomainObjectInterface` carries `@api`, and that no class carries `@api`
  and `@internal` at once.
- [x] 1.4 Show the tests can fail: remove `final` from one event class,
  separately add an unused event class, separately add an event only its own
  file creates, separately drop one `@api` tag, separately tag one `@api`
  class `@internal`, and separately name one class on the page that has none;
  every run is red. Restore each.

## 2. Upgrade check

- [x] 2.1 Extend the XCLASS test of `ConfigurationCheckerTest` with a domain
  model of the fixture extension, expecting a notice; watch it fail.
- [x] 2.2 Report an XCLASS of a domain model as a notice in
  `ConfigurationChecker`, and update the XCLASS row of the upgrade check
  chapter.

## 3. Integrator page

- [x] 3.1 Create `academic-base/Documentation/Developers/Index.rst` and
  `Developers/ExtensionPoints/Index.rst`, and add the section to the card grid
  and the toctree of `academic-base/Documentation/Index.rst`.
- [x] 3.2 Write the public API list, the not-API statement with the XCLASS
  statement and the five open controllers, the minimum set of extension points
  (existing and planned, each marked), and the changelog rule for changes to
  listed API.
- [x] 3.3 List every current event with extension, dispatch location and what
  a listener may change; verify each dispatch in the source before listing
  it.
- [x] 3.4 List the types the events hand over, the five interfaces, the
  services and classes the manuals already tell projects to name or inject,
  the two traits and the domain models.
- [x] 3.5 Link the page from the `Developers/` pages of `academic_persons`,
  `academic_partners`, `academic_projects` and `category_types`, and from the
  index page of every other extension the page lists API of.
- [x] 3.6 Add an `@api` docblock tag to every class, interface, trait and enum
  the page lists that does not carry one yet; the test of 1.2 proves the page
  and the tags name the same set.

## 4. Contributor rule

- [x] 4.1 Add an "Extension points" section to
  `docs/architecture/class-design.md`: event naming, `final`, setters only for
  mutable parts, the academic_base plugin action context as the one context
  type, `@api` on listed API, and "dispatched and tested";
  mention the architecture test and extend the entry of the page in
  `docs/architecture/Index.md`.
- [x] 4.2 Name the new test where `AGENTS.md` and `docs/` describe the tests
  of `packages-dev/monorepo-shared`.

## 5. Changelog

- [x] 5.1 Add `academic-base/Documentation/Changelog/3.0/Important-ExtensionPointPolicy.rst`
  from `Build/Documentation/Templates/Changelog-Important.rst`, stating what
  is API, that XCLASSing or subclassing anything else is unsupported, and
  that the upgrade check reports an XCLASS of a model as a notice.

## 6. File the issue

- [x] 6.1 After implementation, file the ACE issue in YouTrack (ACE-749),
  rename the change to `ace-749-extension-point-policy`, and commit in TYPO3
  Core format as `[TASK] ACE-749: State the extension point policy`.

## 7. Definition of done

- [x] 7.1 `lintPhp` green.
- [x] 7.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [x] 7.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`.
- [x] 7.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 7.5 `docs/` and the `Documentation/Changelog/3.0/` entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 7.6 Archive the change as the last commit of the pull request.
