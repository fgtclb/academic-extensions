## 1. Tests first

- [ ] 1.1 Run both `UsingDefaultProfileFactoryOnlyTest` classes and record
  that they are green. They are the byte-for-byte proof of the defaults and
  must stay unchanged.
- [ ] 1.2 Add a fixture package whose `Settings.yaml` maps `mobile` to a
  phone with type `mobile` and a column to the contract position. Add a
  functional test expecting the second phone and the position. Show it fails
  before the change (only telephone and fax are written).
- [ ] 1.3 Add tests for a field mapped to `''`: the editor value stays. Add
  tests for an emptied `mobile` source: the imported record is removed and
  manual records stay. Add a test that a second run creates no duplicate.
  Show that the `''` test fails before the change.
- [ ] 1.4 Add unit tests for the normalisation: defaults, lists, and an
  unknown property name throwing its exception code.

## 2. Implementation

- [ ] 2.1 Add the `frontendUserSync` defaults to `Settings.yaml`, with a
  comment block, and the normalised sub-graph with `__set_state()`. Verify
  1.4.
- [ ] 2.2 Add the stateless mapper and delegate `ProfileFactory` to it.
  Verify 1.1 to 1.3 on v13 and v14.

## 3. Documentation

- [ ] 3.1 Document the map in the `academic_persons` `Documentation/`
  configuration chapter and add
  `Documentation/Changelog/3.0/Feature-ConfigurableFrontendUserMapping.rst`.
  Verify the rendering.
- [ ] 3.2 Extend `docs/architecture/frontend-user-contact-import.md` with the
  mapping and the identifier rule for additional entries. Verify
  `lintMarkdown -n`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack (related to
  ACE-278), rename the change to `ace-<NNN>-<slug>`, and commit in TYPO3 Core
  format as `[FEATURE] ACE-<NNN>: <subject>`.

## 5. Definition of done

- [ ] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v13 and v14, each after its own `composerUpdate`.
- [ ] 5.2 `functional` also on PostgreSQL for the synchronisation tests,
  because they write.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the extension's `Documentation/` changelog updated in
  the same change.
- [ ] 5.5 Archive the change as the last commit of the pull request.
