## 1. TypoScript fields of a TypoScript record

- [x] 1.1 `atImportResolves()` became `atImportFiles($value, $fileSuffix)`, returning the files an import reads; "resolves" is now "the list is not empty". `tsConfigReferences()` needed no change - it extracts paths and never looked at the suffix, so only the resolver was parameterised. The existing shape data provider is green unchanged. Original text: make `tsConfigReferences()` suffix-agnostic and turn
  `atImportResolves()` into `atImportFiles()`, returning the files a reference
  resolves to. The four shapes and their order stay exactly as
  `TreeFromLineStreamBuilder::processAtImport()` has them; unit-level coverage
  of the shapes already exists through the existing data provider and must stay
  green unchanged.
- [x] 1.2 Done, as `checkRecordTypoScript()`. The `sys_template` query lost its `include_static_file <> ''` condition on the way, because a record can carry TypoScript, a clear flag or a static template independently of each other. Read `constants` and `config` of the rows the static template check
  already selects, and report an academic `@import` that resolves to no file as
  `typoscript-import`, naming the record, the field and the reference.
  Functional test with one resolving and one dead import per field, a foreign
  extension that is not reported, and a hidden record that is not reported;
  shown to fail with the check removed and with every import made to resolve.
- [x] 1.3 Done. Report an academic `<INCLUDE_TYPOSCRIPT:` in either field as
  `typoscript-syntax`, whether or not the file exists, the way the TSconfig
  check already does. Functional test with an include of a file that exists;
  shown to fail when the finding is made conditional on the file being gone.

## 2. A clear flag that discards a site set contribution

- [x] 2.1 Done, as `clearedSetBranches()`. Report a visible TypoScript record on the root page of a site with
  declared site sets that clears the Constants (`clear & 1`) or the Setup
  (`clear & 2`) branch, as `set-branch-cleared`, naming the site, the record
  and the branch. The site condition is `Site::getSets() !== []`, not the
  `@internal` `isTypoScriptRoot()` — see `design.md`.
- [x] 2.2 Done, plus a fourth case: a record clearing **both** branches, because the backend button that produces this state writes both bits and the message has to name both. Functional test with three sites: one with sets and a clearing
  record, one with sets and a record that clears nothing, one without sets and
  a clearing record. Only the first is reported; shown to fail with the check
  removed and with the branch condition dropped.

## 3. Following a page TSconfig reference that resolves

- [x] 3.1 Done, as `followTsConfigFiles()`. Follow a reference that resolves, scan the file it names and report
  the academic references inside it that do not resolve, naming the page or
  site that leads to the file and the file itself. Any resolving file is
  followed, not only an academic one; only academic references are reported.
- [x] 3.2 Guarded with a `seen` set of absolute file names, and the test asserts
  exactly one finding for a pair of files importing each other. **The warning in
  this task turned out to be the point.** With the `seen` set removed the run did
  not fail, it exhausted the memory limit and took the whole process down - 28
  tests in, with a fatal error pointing at an unrelated line. A second net was
  added for that: `MAX_TSCONFIG_DEPTH = 25`. Correctness still comes from the
  `seen` set, which bounds the recursion by the number of distinct files; the
  depth bound is what turns a defect in it into 13 findings instead of 1, which
  is a defect that can be seen and tested. Re-measured with the same mutation:
  "Failed asserting that actual size 13 matches expected size 1".
- [x] 3.3 Done - `tests/test-upgrade-check-project` gained a `Configuration/TSconfig/Project.tsconfig` that resolves and imports a renamed academic path, and `ConfigurationCheckerTest` loads it. Functional test with a project file that resolves and imports a
  renamed academic path, which is the case this task exists for; shown to fail
  when the recursion is removed.

## 4. The command guard

- [x] 4.1 Done, guarded before the "name at least one --override-path" branch so the message is the precise one. Covered for the option alone and together with `--site`. Reject `--upstream-path` given without `--override-path`, with a
  message that says what to give instead. Extend the existing data provider of
  `UpgradeCheckCommandConfigurationTest`; shown to fail without the guard.

## 5. Documentation

- [x] 5.1 Done. Add the three findings to the tables in
  `academic-base/Documentation/UpgradeCheck/Index.rst` and in
  `docs/architecture/upgrade-checks.md`, with the core method each one
  reproduces, and move the two items this change implements out of the "does
  not check" lists of both.
- [x] 5.2 Done. Add the remaining gap — the `@import` chain of a TypoScript record is
  read one level deep only — to both "does not check" lists.
- [x] 5.3 Done:
  `academic-base/Documentation/Changelog/3.0/Feature-UpgradeCheckTypoScriptRecords.rst`
  from `Build/Documentation/Templates/Changelog-Feature.rst`.
- [x] 5.4 Done. Update the `clear = 3` trap section of
  `docs/architecture/typoscript-and-site-sets.md` to name the new finding as
  its detection, the way the double-parse section already names
  `set-and-static-template`.

## 6. Definition of done

- [x] 6.1 `lintPhp` green.
- [x] 6.2 Green on SQLite and PostgreSQL, 2348 tests. After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`, on SQLite and on PostgreSQL.
- [x] 6.3 Green on SQLite and PostgreSQL, 2363 tests. After `-t 14 -s composerUpdate`: the same with `-t 14`.
- [x] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.5 `docs/` and the `Documentation/Changelog/3.0/` entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 6.6 Twelve mutations, each the one its test exists to catch rather than
  the removal of a whole check: the group dropped, the wrong file suffix, only
  one of the two fields read, the legacy syntax ignored, the `sys_template`
  query filtered again, the cleared-branch check dropped, the `getSets()`
  condition dropped, only the Setup bit read, a resolving reference not
  followed, a foreign file not followed, the `seen` set removed, and the command
  guard removed. Every one produced a red run naming the test that covers it.
- [x] 6.7 Archived as the last commit of the pull request. Verified afterwards that both promoted specs changed and that the selected-folder scenario reached openspec/specs/. It
  modifies two capabilities and adds none, so both delta files carry
  `## MODIFIED Requirements` or `## ADDED Requirements` against the existing
  spec — check the archive really updates
  `openspec/specs/academic-base/upgrade-check/spec.md` and
  `upgrade-configuration-check/spec.md`, which `openspec validate` does not
  verify.

## 7. Review round

One blocker and five should-fix items; each was verified against both installed
cores before it was applied, and each fix was mutation-tested.

- [x] 7.1 **Blocker, in code this change added.** `tsconfig_includes` was
  resolved with `@import` semantics. Core reads a selected value with one exact
  file lookup - no folder, no wildcard, no appended suffix, no suffix
  requirement - so the check reported the contents of folders TYPO3 never opens
  and missed a selected file of an unusual suffix that it does read.
  `selectedTsConfigPath()` reproduces the lookup, and a selected **folder** now
  gets a finding of its own, because core's `file_exists()` accepts a directory
  and then reads nothing from it while raising a warning.
- [x] 7.2 **Page TSconfig resolves two suffixes, not one.**
  `$atImportTypeToSuffixMap` maps `tsconfig` to `['typoscript', 'tsconfig']`
  and core loops them. The change asserted the opposite as a measured premise
  in three places - the code, `docs/` and this design - and reported a
  resolving `.typoscript` import as dead. Corrected everywhere, and the shape
  data provider gained the two cases that pin it.
- [x] 7.3 The nested finding named the import *expression* instead of the file,
  which for a folder import named a folder as "the file". It names the file
  now, as an `EXT:` path.
- [x] 7.4 The relative-lookup justification stopped being true for followed
  files - core sets the path of a node built from a file, so a relative import
  resolves there. Under-report only; named in both lists and in the code.
- [x] 7.5 Every visible `sys_template` row is read while core reads one per page
  along a rootline, so a second record on a page is reported although TYPO3
  never tokenizes it. The pre-existing checks share that simplification; named
  in both lists rather than fixed.
- [x] 7.6 Two messages of the record check were missing what their page
  TSconfig twins carry: the "extension is not installed" branch, whose absence
  gave advice that cannot be followed, and the note that `@import` of a folder
  does not descend where `DIR:` did.
- [x] 7.7 `FileNameValidator` is applied to every file read from a folder or a
  wildcard, as core does, so a name the deny pattern refuses is read by
  neither.
- [x] 7.8 Tests: the mutation sweep found one of its own gaps - the "a selected
  file that resolves is followed" test named a *project* file and left the
  academic branch of the same method uncovered. Seven mutations now, each the
  one its test exists to catch; the per-page scope of the `seen` set and both
  new message branches are covered too.
- [x] 7.9 The changelog sample printed a truncated message as if it were
  verbatim output.

## 8. Review round 2

No blockers. Four should-fix items and five nits, all verified against both
installed cores.

- [x] 8.1 **`FileNameValidator` covered two of the four `@import` shapes.** Core
  guards all four, and when it refuses a name it contributes **nothing** - so
  the import is dead. The check returned the file instead, called the import
  resolved, produced no finding, and then read and reported the lines of a file
  nobody parses. Both single-path branches are guarded now, the fixture
  extension ships a `Denied.php.tsconfig`, and removing either guard turns the
  new test red.
- [x] 8.2 The new suffix paragraph had the asymmetry **backwards**: the record's
  suffix list is a *subset* of the page TSconfig one, so a `.typoscript`-only
  folder resolves for both, and it is a `.tsconfig`-only folder that resolves
  for page TSconfig alone. Corrected.
- [x] 8.3 Two backend labels were invented rather than read:
  `pages.tsconfig_includes` is :guilabel:`Include static Page TSconfig (from
  extensions)` and `sys_template.include_static_file` is :guilabel:`Include
  TypoScript sets` on both versions. The second was pre-existing, from ACE-713.
- [x] 8.4 **The selected-folder finding was behaviour without a spec** - the
  same blind spot ACE-713 hit from the other side, and `openspec validate`
  cannot see it. The `MODIFIED` requirement now covers a selected value naming
  something other than a file, with a scenario.
- [x] 8.5 The dedupe in `atImportFilesOfType()` is unreachable: each pass yields
  only files ending in its own suffix, so the sets are disjoint. The docblock
  claimed "a file matching both passes is read once here and twice by core",
  which is a behaviour that cannot occur. Replaced by what is true.
- [x] 8.6 `displayPath()` took the first matching package rather than the
  longest, so a package nested inside another would be named after its host.
- [x] 8.7 Nits: "the frontend raises a warning" - page TSconfig is resolved in
  the backend as much as in the frontend; the `TsConfigImport` docblock still
  said "does not exist" after the folder case joined it; and two consecutive
  "The finding names ..." sentences were merged.

## 9. Review round 3

No blockers. One item from round 2 had been **recorded as done without being
finished**, which is the finding worth keeping.

- [x] 9.1 8.7 said two consecutive "The finding names ..." sentences were
  merged. The first was extended to absorb the second and the second was never
  deleted, so the paragraph said the same thing twice - worse than before the
  "fix", and ticked off. Deleted.
- [x] 9.2 The deny-pattern finding stated a reason that is not true: "matches
  no file of the installed version", about a file that is there and that the
  integrator can see. The three causes are told apart now - the extension is
  gone, nothing matches, or a file matches whose *name* TYPO3 refuses - in one
  `deadImportMessage()` used by both the page TSconfig and the record side,
  which also removed a duplicated branch.
- [x] 9.3 The new spec sentence over-claimed: it said "a selected value naming
  something other than a file", without the academic qualifier the code
  applies. Scoped.
- [x] 9.4 A substituted line was not re-wrapped (96 characters against a file
  that wraps at 80), and the manual lost `pages.tsconfig_includes` from the
  sentence when the wrong label was replaced - the column name is what a grep
  finds. Both restored.
