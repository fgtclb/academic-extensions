## 1. Backport analysis

- [x] 1.1 Diff every file `main`'s change touched against this branch and
      record what does not carry over.
- [x] 1.2 Establish what of `main`'s change is *not* in scope and say so in
      `proposal.md` and in the changelog entry.

## 2. The query

- [x] 2.1 `PartnerDemand::setDrawableOnly()`, the constraint in
      `PartnerRepository`, and the same requirement in `findGeoLocated()`.
- [x] 2.2 Set the flag in `mapAction()` **and not in `listAction()`**; a
      rendering test for both plugins is what holds that.
- [x] 2.3 Give the geolocation fixture the coordinate columns it never had, and
      a row that claims a status without carrying one.

## 3. The model

- [x] 3.1 `Partner::isDrawable()`, documenting both directions in which it
      disagrees with the query.
- [x] 3.2 Unit tests including a single zero, a negative zero, a partner south
      and west of Greenwich, and a non-finite coordinate. Run them against two
      wrong rules rather than against the unchanged code, which has no method.

## 4. The template

- [x] 4.1 Render a message and no assets when there is nothing to draw, telling
      an empty filter result apart from an empty geocoding result.
- [x] 4.2 The label in both shipped languages.

## 5. Translations

- [x] 5.1 `allowLanguageSynchronization` on both columns, with a DataHandler
      test that a new translation inherits and follows.
- [x] 5.2 The upgrade wizard, with a test for rows in sync, out of sync,
      detached, deleted and in a workspace.
- [x] 5.3 `GeocodeCommand` through the DataHandler with a synthetic backend
      user, failing on a refused write; a functional test with the geocoding
      service stubbed.

## 6. Documentation

- [x] 6.1 `docs/architecture/translation-synchronization.md`, written for this
      branch, linked from the section index.
- [x] 6.2 `Documentation/Changelog/2.4/Important-*.rst`, naming the wizard and
      the string comparison that stays.

## 7. Definition of done

- [x] 7.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v12 and v13, each after its own `composerUpdate`; `functional` on
      postgres as well, because the change writes.
- [x] 7.2 `lintMarkdown -n`, `checkRstRenderingAll` and the node suites green.
- [x] 7.3 Commit message in TYPO3 Core format referencing ACE-709; the change
      archived as the last commit of the same pull request.
