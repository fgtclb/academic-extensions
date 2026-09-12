## 1. Prerequisite

- [ ] 1.1 Verify `ace-tbd-settings-driven-fe-user-mapping` is merged, for the
  added-key mapping test in 2.2.

## 2. Tests first

- [ ] 2.1 Add a fixture extension with a listener calling `skip()`. Test that
  `academic:createprofiles` creates nothing for that user and continues with
  the next one. Show it fails before the change (a profile is created). Add
  the update variant: values unchanged and no update event dispatched.
- [ ] 2.2 Add a listener adding `ldap.room`, with a fixture mapping
  `contract.room: ldap.room`. The room is persisted. Show it fails before the
  change.
- [ ] 2.3 Add a listener of the after-mapping event setting the gender. Add a
  custom factory fixture returning `null`: nothing is persisted and no event
  is dispatched. Show both fail before the change.

## 3. Implementation

- [ ] 3.1 Add both events and dispatch them from the abstract factory. Make
  the protected creation method nullable. Verify 2.1 to 2.3 on v13 and v14,
  and verify the existing factory tests stay green.

## 4. Documentation

- [ ] 4.1 Add
  `academic-persons/Documentation/Changelog/3.0/Feature-FrontendUserSyncDataEvents.rst`
  with a stateless listener example using TYPO3's `#[AsEventListener]`.
  Document the `<source>.<key>` convention for added keys (not enforced).
  Extend the developer chapter. Verify the rendering.
- [ ] 4.2 Extend `docs/architecture/frontend-user-contact-import.md` with the
  event order and the skip semantics. Verify `lintMarkdown -n`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-<slug>`, and commit in TYPO3 Core format as
  `[FEATURE] ACE-<NNN>: <subject>`.

## 6. Definition of done

- [ ] 6.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v13 and v14, each after its own `composerUpdate`.
- [ ] 6.2 `functional` also on PostgreSQL for the synchronisation tests,
  because they write.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the extension's `Documentation/` changelog updated in
  the same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
