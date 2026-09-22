## 1. TypoScript fields of a TypoScript record

- [ ] 1.1 Make `tsConfigReferences()` suffix-agnostic and turn
  `atImportResolves()` into `atImportFiles()`, returning the files a reference
  resolves to. The four shapes and their order stay exactly as
  `TreeFromLineStreamBuilder::processAtImport()` has them; unit-level coverage
  of the shapes already exists through the existing data provider and must stay
  green unchanged.
- [ ] 1.2 Read `constants` and `config` of the rows the static template check
  already selects, and report an academic `@import` that resolves to no file as
  `typoscript-import`, naming the record, the field and the reference.
  Functional test with one resolving and one dead import per field, a foreign
  extension that is not reported, and a hidden record that is not reported;
  shown to fail with the check removed and with every import made to resolve.
- [ ] 1.3 Report an academic `<INCLUDE_TYPOSCRIPT:` in either field as
  `typoscript-syntax`, whether or not the file exists, the way the TSconfig
  check already does. Functional test with an include of a file that exists;
  shown to fail when the finding is made conditional on the file being gone.

## 2. A clear flag that discards a site set contribution

- [ ] 2.1 Report a visible TypoScript record on the root page of a site with
  declared site sets that clears the Constants (`clear & 1`) or the Setup
  (`clear & 2`) branch, as `set-branch-cleared`, naming the site, the record
  and the branch. The site condition is `Site::getSets() !== []`, not the
  `@internal` `isTypoScriptRoot()` — see `design.md`.
- [ ] 2.2 Functional test with three sites: one with sets and a clearing
  record, one with sets and a record that clears nothing, one without sets and
  a clearing record. Only the first is reported; shown to fail with the check
  removed and with the branch condition dropped.

## 3. Following a page TSconfig reference that resolves

- [ ] 3.1 Follow a reference that resolves, scan the file it names and report
  the academic references inside it that do not resolve, naming the page or
  site that leads to the file and the file itself. Any resolving file is
  followed, not only an academic one; only academic references are reported.
- [ ] 3.2 Guard the recursion with a `seen` set of absolute paths. Functional
  test with two files importing each other asserts the run terminates and
  reports each unresolved reference once; shown to fail without the guard
  (the test must be written so that it fails rather than hangs — assert on a
  bounded run, not on a timeout).
- [ ] 3.3 Functional test with a project file that resolves and imports a
  renamed academic path, which is the case this task exists for; shown to fail
  when the recursion is removed.

## 4. The command guard

- [ ] 4.1 Reject `--upstream-path` given without `--override-path`, with a
  message that says what to give instead. Extend the existing data provider of
  `UpgradeCheckCommandConfigurationTest`; shown to fail without the guard.

## 5. Documentation

- [ ] 5.1 Add the three findings to the tables in
  `academic-base/Documentation/UpgradeCheck/Index.rst` and in
  `docs/architecture/upgrade-checks.md`, with the core method each one
  reproduces, and move the two items this change implements out of the "does
  not check" lists of both.
- [ ] 5.2 Add the remaining gap — the `@import` chain of a TypoScript record is
  read one level deep only — to both "does not check" lists.
- [ ] 5.3 Add
  `academic-base/Documentation/Changelog/3.0/Feature-UpgradeCheckTypoScriptRecords.rst`
  from `Build/Documentation/Templates/Changelog-Feature.rst`.
- [ ] 5.4 Update the `clear = 3` trap section of
  `docs/architecture/typoscript-and-site-sets.md` to name the new finding as
  its detection, the way the double-parse section already names
  `set-and-static-template`.

## 6. Definition of done

- [ ] 6.1 `lintPhp` green.
- [ ] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`, on SQLite and on PostgreSQL.
- [ ] 6.3 After `-t 14 -s composerUpdate`: the same with `-t 14`.
- [ ] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.5 `docs/` and the `Documentation/Changelog/3.0/` entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 6.6 Every new behaviour was shown to fail without the change, and the
  mutation that was used is the one the test exists to catch — not merely the
  removal of the whole check. ACE-713 shipped two tests that passed for every
  mutation they were written for.
- [ ] 6.7 Archive the change as the last commit of the pull request. It
  modifies two capabilities and adds none, so both delta files carry
  `## MODIFIED Requirements` or `## ADDED Requirements` against the existing
  spec — check the archive really updates
  `openspec/specs/academic-base/upgrade-check/spec.md` and
  `upgrade-configuration-check/spec.md`, which `openspec validate` does not
  verify.
