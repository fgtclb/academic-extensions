## 1. Test first, red on this branch

- [x] 1.1 Add a functional test for `academic_partners` that compiles the
  backend form of a partnership record through the form data compiler, modelled
  on **this branch's** copy of the `academic_base` form data provider test, with
  a fixture whose partner titles are in a different order than their record
  order; assert the complete order of the select entries. Verify it **fails** on
  the unchanged configuration and record the failure output — do not inherit the
  red proof from `main`.
- [x] 1.2 Add a scenario asserting the empty placeholder entry is first, and
  verify it passes before and after the change.
- [x] 1.3 Add a scenario with a title starting with a diacritic (`Ö` between `O`
  and `P`) and verify it fails on the unchanged configuration for the ordering
  reason, not for a missing record.
- [x] 1.4 Add a scenario asserting deleted partner pages stay absent from the
  entries, and scenarios pinning that hidden partner pages are offered on
  TYPO3 v13 and not on v12; verify they pass before and after. This task
  assumed hidden pages were absent on both versions, which they are not — see
  the decision "Hidden partners are offered, and stay that way" in
  `design.md`.
- [x] 1.5 Verify the new test runs on TYPO3 v12 **and** v13, each after its own
  `composerUpdate` — the mechanism is claimed to need no version split, and this
  is what proves it.

## 2. Implementation

- [x] 2.1 Declare the ascending label order on the partner field of
  `packages/fgtclb/academic-partners/Configuration/TCA/tx_academicpartners_domain_model_partnership.php`.
  The line already exists in pull request #642; rebase that pull request onto
  the merged change rather than writing it again, keeping its author. Verify
  tests 1.1 and 1.3 now pass on v12 and v13.
- [x] 2.2 Take the red proof by mutation once more if the line arrived by
  rebase: back the file up, remove the declaration, watch the test fail, restore
  and compare checksums. `git checkout --` cannot restore it while the change is
  uncommitted.
- [x] 2.3 Verify the diff contains nothing but that declaration, the test and
  the documentation — in particular no change to the item handler and no
  repository ordering, which would be ACE-491 arriving here by accident.

## 3. Documentation

- [x] 3.1 Add
  `packages/fgtclb/academic-partners/Documentation/Changelog/2.4/Feature-PartnerSelectIsSortedByLabel.rst`
  (template `Build/Documentation/Templates/Changelog-Feature.rst`), describing
  the previous order as the one the database returned — **not** the page tree
  order, which is true on `main` only. Verify it renders with
  `checkRstRenderingSingle academic-partners`; the folder's index globs
  `Feature-*`, so no index edit is needed.
- [x] 3.2 Check `docs/` on this branch for a statement about backend select
  ordering and update it if one exists; state in the pull request when nothing
  needed a change.

## 4. Pull request

- [x] 4.1 Rebase #642 onto the merged change, amend it with the test and the
  changelog entry, and reword its subject to
  `[FEATURE] ACE-669: Sort the partner select by label` in TYPO3 Core format,
  keeping Maxim Ertler as the author. Verify with `git log --format='%an <%ae>'`.
- [x] 4.2 Add the `Releases: 2` footer, and link the pull request on ACE-669
  next to the `main` one.
- [x] 4.3 Archive the change as the last commit of that same pull request.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v12, all green.
- [x] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [x] 5.3 `functional -d postgres` for `academic_partners` on both core
  versions — the previous order was database dependent, which is exactly what
  PostgreSQL shows and SQLite hides.
- [x] 5.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.5 The extension's `Documentation/Changelog/2.4/` entry written, and
  `docs/` checked.
- [x] 5.6 Commit message in TYPO3 Core format with the verified `ACE-669`
  reference and no attribution of any kind.
