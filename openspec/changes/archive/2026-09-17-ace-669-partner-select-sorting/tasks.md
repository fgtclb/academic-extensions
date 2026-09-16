## 1. Test first

- [x] 1.1 Add a functional test for `academic_partners` that compiles the
  backend form of a partnership record through the form data compiler, with a
  fixture whose partner titles are in a different order than both the `uid`
  order and the page tree `sorting` order; assert the complete order of the
  select entries. Verify it **fails** on the unchanged configuration, and
  record the failure output.
- [x] 1.2 Add a scenario asserting the empty placeholder entry is the first
  entry, and verify it passes before and after the change — it pins behaviour
  the change must not break.
- [x] 1.3 Add a scenario with a title starting with a diacritic (`Ö` between
  `O` and `P`) and verify it fails on the unchanged configuration for the
  ordering reason, not for a missing record.
- [x] 1.4 Add a scenario asserting deleted partner pages are absent from the
  entries, and a second one asserting hidden partner pages are still
  **offered**; verify both pass before and after. This task asked for hidden
  pages to be absent as well — they are not, and never were. See the decision
  "Hidden partners are offered, and stay that way" in `design.md`.

## 2. Implementation

- [x] 2.1 Declare the ascending label order on the partner field of
  `packages/fgtclb/academic-partners/Configuration/TCA/tx_academicpartners_domain_model_partnership.php`.
  The line already exists in pull request #641; rebase that pull request onto
  the merged change rather than writing it again, keeping its author. Verify
  tests 1.1 and 1.3 now pass on TYPO3 v13 and v14.
- [x] 2.2 Verify the diff of the extension contains nothing but that
  declaration, the test and the documentation — in particular no change to the
  item handler and no change to any repository ordering.

## 3. Documentation

- [x] 3.1 Add `packages/fgtclb/academic-partners/Documentation/Changelog/2.4/Feature-PartnerSelectIsSortedByLabel.rst`
  (template `Build/Documentation/Templates/Changelog-Feature.rst`), naming the
  previous order for this branch, and verify it renders with
  `checkRstRenderingSingle academic-partners`. The `2.4` index globs
  `Feature-*`, so no index edit is needed — verify that in the rendered output.
- [x] 3.2 Check `docs/` for a statement about backend select ordering and
  update it if one exists; state in the pull request when nothing needed a
  change.

## 4. Pull request

- [x] 4.1 Rebase #641 onto the merged change, amend it with the test and the
  changelog entry, and reword its subject to
  `[FEATURE] ACE-669: Sort the partner select by label` in TYPO3 Core format,
  keeping Maxim Ertler as the author. Verify with `git log --format='%an <%ae>'`.
- [x] 4.2 Archive the change as the last commit of that same pull request.

## 5. Backport to branch `2`

Tasks 5.3 and 5.4, and 6.7 below, are carried out on branch `2` and are tracked
by the change of the same name there, which is archived in its own pull request.
They stay unticked here because this branch cannot verify them.

- [x] 5.1 Write the file level backport analysis
  (`docs/workflow/backporting.md`, the three measurement commands) into
  `.agent/reports/`, and state which files are byte-identical.
- [x] 5.2 Create the change on branch `2` with `openspec new change` — never a
  copy of this directory, specs are branch scoped — re-derive the artifacts,
  and put the changelog entry in that branch's `2.4` folder with the wording
  the different starting order needs.
- [ ] 5.3 Take the red proof again **on branch `2`**; do not inherit it from
  this branch. Back the file up and compare checksums rather than restoring
  with `git checkout --`, which cannot restore an uncommitted change.
- [ ] 5.4 Rebase #642 the same way as #641 and archive the branch `2` change as
  its last commit.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [x] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green.
- [x] 6.3 `functional -d postgres` for `academic_partners` on both core
  versions — the change is about ordering, which is where PostgreSQL diverges,
  even though this ordering happens in PHP.
- [x] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.5 The extension's `Documentation/` changelog updated in the same
  change, and `docs/` checked.
- [x] 6.6 Commit message in TYPO3 Core format with the verified `ACE-669`
  reference and no attribution of any kind.
- [ ] 6.7 The same gates for TYPO3 v12 and v13 on branch `2`.
