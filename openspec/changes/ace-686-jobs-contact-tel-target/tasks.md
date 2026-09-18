## 1. Backport analysis

- [x] 1.1 Read this branch's own `AGENTS.md` and
      `docs/workflow/backporting.md` before anything else — several rules here
      are inverted rather than merely different from `main`.
- [x] 1.2 File-level diff: `Resources/Private/Partials/Job/Contact.html` is
      byte-identical to the copy on `main`, so the patch is a literal copy.
      `Templates/Job/Show.html:49` is its only call site here too, and the
      `contact` guard at `Show.html:37` is the same.
- [x] 1.3 API check: `f:replace` is already in use on this branch in
      `academic-persons` (`Profile/Contract/Field.html` and
      `Templates/Profile/Detail.html`), so it is proven on TYPO3 v12. No
      version switch is needed.
- [x] 1.4 Harness parity: this branch's `jobPages.csv` carries **no contact
      columns at all** and the plugin test class has no contact assertion, so
      the coverage is written here rather than adapted. That is the larger half
      of this change.

## 2. Tests first

- [x] 2.1 Add `Tests/Functional/Plugins/Fixtures/AcademicJobsListAndDetailPlugin/jobPages_contactPhone.csv`
      with the three contact columns and three jobs: a number stored with
      spaces, one stored without, and a contact with a name and an e-mail and
      no phone. A separate fixture, because three existing tests assert job
      counts and list contents against `jobPages.csv`.
- [x] 2.2 Parameterise `setUpTestCase()` with `string $dataSet = 'jobPages'` as
      its first argument, the signature the copy on `main` has. Every existing
      call site stays untouched: the only call that passes the language flag
      passes it by name.
- [x] 2.3 Add the three tests — the dialable target with the label unchanged,
      a number without spaces reaching the target unchanged, and a contact
      block without a phone row. Run them against the unchanged partial and
      record which fails: only the first can, the other two are regression
      guards that hold either way.

## 3. Implementation

- [x] 3.1 Build the link target with `f:replace` in `Job/Contact.html`, with
      the same short `f:comment` as `main`. Verified byte-identical to the
      patched file on `main` by diffing the two.
- [x] 3.2 Revert the line on purpose, watch 2.3's first test go red (1 of 3
      failures on TYPO3 v12), restore from a backup copy and compare the md5 —
      `git checkout --` restores the committed state, not the patched one.

## 4. Documentation

- [x] 4.1 Add
      `packages/fgtclb/academic-jobs/Documentation/Changelog/2.4/Important-JobContactPhoneLinkIsDialable.rst`:
      the old and the new target, the unchanged link text, that an override of
      the partial keeps its own output, and that the fix is about ordinary
      spaces and not about validating what is stored. `2.4/` is this branch's
      changelog directory; `3.0/` does not exist here and is not created.
      Required by `docs/workflow/changelog-and-documentation.md`, section
      *When a change needs a changelog entry* — the rendered output of an
      installation that changed nothing of its own is different after the
      update (ACE-685).
- [x] 4.2 No `docs/` page: this change adds no concept the repository
      documentation describes. Stated in the pull request.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` green for TYPO3 v12.
- [x] 5.2 `composerUpdate`, then the same suites green for TYPO3 v13.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 Commit as `[BUGFIX] ACE-686: Make the contact phone dialable` in
      TYPO3 Core format, no attribution of any kind.
- [x] 5.5 Archive this change as the last commit of the backport pull request,
      which updates this branch's own specs.
