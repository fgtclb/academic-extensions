## 1. Tests first, on TYPO3 v13

- [ ] 1.1 Add a functional plugin test: a finder on page 1 with
  `settings.listPid = 2` renders a form targeting page 2 in the namespace
  `tx_academicprograms_programlist`, with the preselected option selected and
  an option without programs disabled. Run it before the change and record
  that the content type is unknown.
- [ ] 1.2 Add a test submitting `demand[filterCollection][degree]` to the
  list page and expecting only matching programs, pinning the shape the
  finder relies on.
- [ ] 1.3 Add a TCA test that saving a finder without `settings.listPid`
  fails validation.

## 2. Registration

- [ ] 2.1 Register the type with `TcaManipulator::addContentElementPlugin()`
  and `addContentElementPluginFlexForm()`, add `pages`/`recursive` to it, and
  configure the plugin uncached in `ext_localconf.php`.
- [ ] 2.2 Add `Configuration/FlexForms/ProgramFinderSettings.xml` and the
  English and German labels (one line per `source`/`target`, two-space
  indentation), with the types under `settings.filter.categoryTypes`; make
  sure `ignoreFlexFormSettingsIfEmpty` covers the finder's field; extend
  `PluginFlexFormTest` for the new data structure on v13 and v14.

## 3. Rendering

- [ ] 3.1 Add `ProgramController::finderAction()` and
  `Templates/Program/Finder.html`; run group 1 green on v13, then on v14.
- [ ] 3.2 Cover the default types (degree, then topic), a configured order,
  and an empty field with site-wide filter types, which the finder offers
  instead of the default, in the plugin test; show the order and fallback
  assertions fail when the setting is ignored.

## 4. Sets and static registration

- [ ] 4.1 Add `Configuration/Sets/ProgramFinder/`, its page TSconfig and its
  TypoScript folder, the static registrations, the aggregate dependency, and
  hide the type in `Configuration/page.tsconfig`.
- [ ] 4.2 Extend `SiteSetDeliveryTest`, `InstallationWideRegistrationTest` and
  `StaticRegistrationTest` so the finder is offered with the set and hidden
  without it; show each new assertion fails before 4.1.

## 5. Documentation

- [ ] 5.1 Add a finder section to `academic-programs/Documentation/`
  (settings, sets, storage advice, sketch of the output) and list the new set
  in "What the sets contain".
- [ ] 5.2 Add `Documentation/Changelog/3.0/Feature-ProgramFinderContentElement.rst`
  and `Documentation/Changelog/3.0/Breaking-ProgramFinderRegisteredUpstream.rst`
  for projects with their own registration, naming what they delete (TCA
  item, plugin configuration, FlexForm, controller code and TSconfig).
- [ ] 5.3 Update any enumeration of component sets in
  `docs/architecture/typoscript-and-site-sets.md`; verify with
  `lintMarkdown -n`.

## 6. File the issue

- [ ] 6.1 After implementation, decide with the maintainer whether ACE-91
  carries the change; otherwise file a new ACE issue in YouTrack and verify the
  key.
- [ ] 6.2 Rename the change to `ace-<NNN>-program-finder-element`.
- [ ] 6.3 Commit as `[FEATURE] ACE-<NNN>: Add a program finder content
  element` in TYPO3 Core format.

## 7. Definition of done

- [ ] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 7.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` and the `Documentation/` changelog entries are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 7.5 Archive the change as the last commit of the pull request.
