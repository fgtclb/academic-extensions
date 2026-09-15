## 1. Tests

- [x] 1.1 Port the update command tests: each window case (expired,
  scheduled, group restricted, expired frontend user) and the e-mail case,
  without the update event this branch does not dispatch.
- [x] 1.2 Port the enable field list test with a TCA override that removes
  `fe_group`.
- [x] 1.3 Take over the create command test and its fixture, identical on
  both branches.
- [x] 1.4 Port the repository tests of the display paths and of the
  synchronisation lookup, in a frontend request.

## 2. Implementation

- [x] 2.1 Add the sync-only helper reading the enable columns from the TCA,
  and use it in `findByFrontendUser()` for `$showHidden = true`.
- [x] 2.2 Take over the provider change, identical on both branches.
- [x] 2.3 Show the tests fail without the change on v12 and v13 (mutations of
  the helper, the display helper and both provider queries).

## 3. Documentation

- [x] 3.1 Add
  `academic-persons/Documentation/Changelog/2.4/Important-SynchronizationIgnoresTheVisibilityWindow.rst`.
- [x] 3.2 Extend `docs/architecture/frontend-user-contact-import.md`.

## 4. Definition of done

- [x] 4.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v12 (PHP 8.1) and v13 (PHP 8.2), each after its own `composerUpdate`.
- [x] 4.2 `functional` also on PostgreSQL for the touched test classes.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 4.4 Archive the change as the last commit of the pull request.
