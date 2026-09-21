## 1. Checker service

- [x] 1.1 Added `ConfigurationFinding`, `ConfigurationFindingKind` and the
  stateless `ConfigurationChecker` with the static template check. The
  criterion is the one core applies, not the TCA item list - see `design.md`.
  `ConfigurationCheckerTest` covers a working folder, a folder without
  TypoScript, an extension that is gone, a foreign extension and a hidden
  record; shown to fail with the check removed and with the restrictions
  changed. The four 2.x paths `ace-tbd-legacy-typoscript-paths` keeps are not
  touched: they hold TypoScript, so they are correctly not reported, and the
  row asserting it belongs to that change.
- [x] 1.2 Added the page TSconfig check for all three forms, with the four
  `@import` shapes core resolves in a data provider. `<INCLUDE_TYPOSCRIPT:`
  got a finding kind of its own and is reported whether or not the file is
  there, because TYPO3 v14 removed the syntax - see `design.md`. Shown to fail
  with the check removed, with every import resolving, and with the legacy
  syntax reported only when unresolved.
- [x] 1.3 Verified: both read it, through
  `TsConfigTreeBuilder::getSitePageTsConfigTree()` and
  `Site::getTSconfig()->pageTSconfig`. Included, covered by
  `ConfigurationCheckerSiteTest`, recorded in `design.md`.
- [x] 1.4 Added, with the aggregate set asserted not to be reported. Shown to
  fail without the check.
- [x] 1.5 Added. The non-conflicting case covers both halves of the rule: a
  static template of another extension, and one below the root page. Shown to
  fail without the check.
- [x] 1.6 Added, with a third case the proposal did not have: an XCLASS of a
  class the installed version no longer ships, which is an error. An XCLASS of
  a class outside the academic extensions is asserted not to be reported.
  Shown to fail without the check and with the `final` branch disabled.
- [x] 1.7 `theCheckChangesNoRecord` asserts both imported CSV data sets after
  a run that reports findings; shown to fail with an `update()` added to the
  checker on purpose.

## 2. Status report

- [x] 2.1 Added `UpgradeConfigurationStatus`, the `Classes/Report/*` exclude
  and the new `Configuration/Services.php` with the same compiler pass and the
  same comment as the persons one.
- [x] 2.2 `UpgradeConfigurationStatusTest` covers one status per finding, the
  single OK status and the label. **The last assertion could not be written**:
  the guard has no observable effect in a functional test instance, which
  always has `typo3/cms-reports` in `.Build/vendor` - measured, both variants
  answer `has()` with `false`. `UpgradeConfigurationStatusWithoutReportsTest`
  asserts what is observable instead - the check and the command work without
  the extension - and `design.md` records why.

## 3. Command check group

- [x] 3.1 Added. The `extension` argument became optional, and the group runs
  on every invocation - see `design.md`.
  `UpgradeCheckCommandConfigurationTest` asserts the exit status for a
  configuration problem, for a notice, and for a run whose template overrides
  are clean; the last one is what fails when the exit status ignores the
  configuration findings, which the first two do not.

## 4. Documentation

- [x] 4.1 Documented the check in `academic-base/Documentation/` (a page linked
  from its `Index.rst`), with one paragraph per finding and how to fix it.
- [x] 4.2 Added `academic-base/Documentation/Changelog/3.0/Feature-UpgradeConfigurationCheck.rst`
  from `Build/Documentation/Templates/Changelog-Feature.rst`.
- [x] 4.3 Updated the double-parse section of
  `docs/architecture/typoscript-and-site-sets.md`, and the configuration group
  was added to `docs/architecture/upgrade-checks.md`, which is where the
  reasoning of the first group already lives.

## 5. File the issue

- [x] 5.1 Filed as **ACE-713** (Story, Open, version 3.0.0, relates to
  ACE-712, ACE-366 and ACE-534), renamed the change to
  `ace-713-upgrade-check-configuration` and replaced the old name in the four
  other changes that referenced it, and committed as
  `[FEATURE] ACE-713: Report stale 2.x configuration`.

## 6. Definition of done

- [x] 6.1 `lintPhp` green.
- [x] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`, on SQLite and on PostgreSQL.
- [x] 6.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`. PostgreSQL was run all the same, because
  `pages.TSconfig` is a mixed case identifier and the fixtures write.
- [x] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.5 `docs/architecture/upgrade-checks.md`,
  `docs/architecture/typoscript-and-site-sets.md`, the two section indexes, the
  extension manual and `Documentation/Changelog/3.0/Feature-UpgradeConfigurationCheck.rst`
  are part of the change; `README.md` and `CONTRIBUTING.md` are untouched.
- [ ] 6.6 Archive the change as the last commit of the pull request.

## 7. Review round

A dedicated review found two blockers and seven should-fix items; every one was
verified against the installed cores before it was applied.

- [x] 7.1 **Blocker.** `pages.TSconfig` and `pages.tsconfig_includes` are
  `l10n_mode` = `exclude`, so every translation carries a byte copy of its
  parent's value and the check reported one finding per language, naming a
  record core never reads page TSconfig from. The query is restricted to
  `sys_language_uid = 0`, and the fixture gained a translation row.
- [x] 7.2 **Blocker.** `--upstream-path` was not in the guard that rejects an
  option without the extension key, so a call that Symfony refused before the
  argument became optional now ran and ignored the option. Guarded, and the
  test became a data provider over all three options.
- [x] 7.3 A `/* ... */` block over several lines was not recognised as a
  comment, which `LossyTokenizer::ignoreUntilEndOfMultilineComment()` skips -
  a false positive for commented out configuration.
- [x] 7.4 A `<INCLUDE_TYPOSCRIPT:` line was cut at the first `>`, so an include
  carrying `condition="[... > ...]"` lost its `source` and was missed. The
  quote aware scan of `parseImportOld()` is reproduced.
- [x] 7.5 The gaps are named in both "does not check" lists: the `constants`
  and `config` fields of a TypoScript record, whose `@import` is dropped just
  as silently, and the content of a page TSconfig file that resolves.
- [x] 7.6 `symfony/yaml` is used directly and is now declared in
  `composer.json`, next to `symfony/serializer`.
- [x] 7.7 The folder renames were attributed to 3.0. They are the 2.4
  restructuring, and at the last released 2.x (`2.3.4`) the page TSconfig
  folder was spelled three different ways across the extensions. Corrected in
  the changelog, the manual and `design.md`.
- [x] 7.8 `aNoticeAloneLetsTheRunSucceed()` asserted `FAILURE`, because the
  fixture it imported carried problems - the delta spec scenario "notice only
  exits with zero" had no covering test for this group. Split into a
  notice-only run and `aNoticeIsListedNextToTheProblems()`.
- [x] 7.9 Two parity slips in the static template check: a value naming no
  folder below the extension makes core **throw** 1651138603 rather than skip
  silently, and whitespace after the extension key is trimmed by core's
  `trimExplode()` and was not by the check. Both fixed, both covered.
- [x] 7.10 Nits: `Site::getTSconfig()` is `@internal` and now says so; an
  `@import` of an extension that is not installed gets the message the
  `tsconfig_includes` branch already had; the advice to rewrite a `DIR:`
  include says that `@import` does not descend; the manual row now mentions
  the site `page.tsconfig`; a deleted and a not-yet-started TypoScript record
  make the restriction requirement observable; the latent `pagets`-only set
  case is recorded in `design.md`.

## 8. Review round 2

- [x] 8.1 **Blocker.** The change falsified two requirements of the merged
  `academic-base/upgrade-check` specification - the exit status now also
  reflects the configuration group, and the extension key is no longer
  required - and the archive would have promoted a specification tree that
  describes behaviour the code does not have. A `## MODIFIED Requirements`
  delta for that capability was added, the proposal lists it as a modified
  capability, and the archive was redone.
- [x] 8.2 A workspace version of a page repeats a finding for the same reason
  a translation did: it carries its own copy of both columns and
  `PageRepository::versionOL()` restores the live uid, so core never reads
  page TSconfig from it. The `pages` query gained a `WorkspaceRestriction(0)`.
  `sys_template` needs none - its TCA declares no `versioningWS`.
- [x] 8.3 `aCommentedOutOrForeignImportIsNotReported()` proved only that a
  comment *starts* being honoured: every line after the block resolved or was
  foreign, so a comment that never ends stayed green. A dead import behind the
  last comment now pins both halves, and both plausible off-by-ones were shown
  to fail.
- [x] 8.4 Nits: the throw message says "on and below the one holding this
  record", because the exception hits the record's own page too; the two
  deliberate divergences of the comment handling and the deliberately looser
  `source=` match are named in the code; and the fragile
  `assertStringNotContainsString('skips', ...)` asserts the positive statement
  instead.

## 9. Review round 3

- [x] 9.1 No blockers. One comment was wrong, and wrong because the round 2
  note it was written from was wrong: `parseOperatorAssignment()` takes the
  rest of the line as the **value**, so a comment opened behind an assignment
  puts the tokenizer into no skip at all, and the two real differences of the
  comment handling point in opposite directions rather than both under-
  reporting. Rewritten with the direction of each one named.
- [x] 9.2 `pageRows()` says what reading the live rows means: a stale import
  introduced in a workspace is reported once it is published, and one
  corrected in a workspace is reported until then.
