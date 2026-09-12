## 1. Architecture test

- [ ] 1.1 Add the event class test to a `packages-dev/` package covered by the
  phpunit glob, asserting that every event class under
  `packages/fgtclb/*/Classes/` is `final` and instantiated by another
  production file; verify it finds all 13 current event classes.
- [ ] 1.2 Show the test can fail: remove `final` from one event class, and
  separately add an unused event class; both runs are red. Restore both.

## 2. Integrator page

- [ ] 2.1 Create `academic-base/Documentation/Developers/Index.rst` and
  `Developers/ExtensionPoints/Index.rst`, and add the section to the toctree
  of `academic-base/Documentation/Index.rst`.
- [ ] 2.2 Write the public API list, the not-API list with the XCLASS
  statement, the minimum set of extension points (existing and planned, each
  marked), and the changelog rule for changes to listed API.
- [ ] 2.3 List every current event with extension, dispatch location and what
  a listener may change; verify each dispatch in the source before listing
  it.
- [ ] 2.4 Link the page from `academic-persons/Documentation/Developers/Index.rst`
  and from the index page of every other extension with a public API.
- [ ] 2.5 Add an `@api` docblock tag to every class, interface and trait the
  page lists that does not carry one yet, and verify the page and the tags
  name the same set.

## 3. Contributor rule

- [ ] 3.1 Add an "Extension points" section to
  `docs/architecture/class-design.md`: event naming, `final`, setters only for
  mutable parts, the academic_base plugin action context as the one context
  type, `@api` on listed API, and "dispatched and tested";
  mention the architecture test and link the section from
  `docs/architecture/Index.md` if the index lists sections.

## 4. Changelog

- [ ] 4.1 Add `academic-base/Documentation/Changelog/3.0/Important-ExtensionPointPolicy.rst`
  from `Build/Documentation/Templates/Changelog-Important.rst`, stating that
  XCLASSing `final` or `@internal` classes is unsupported.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-extension-point-policy`, and commit in TYPO3 Core
  format as `[DOCS] ACE-<NNN>: State the extension point policy`.

## 6. Definition of done

- [ ] 6.1 `lintPhp` green.
- [ ] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 6.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`.
- [ ] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.5 `docs/` and the `Documentation/Changelog/3.0/` entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 6.6 Archive the change as the last commit of the pull request.
