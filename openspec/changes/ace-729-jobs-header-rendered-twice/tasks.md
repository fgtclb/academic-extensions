## 1. Tests first

- [x] 1.1 Port the header tests of `main` to both job test classes and the
  bite jobs test: header layouts "Default", 2 and "Hidden", switch off on the
  core layout and on with a fixture layout without a header, counted in the
  DOM. Record that they fail against the unchanged templates on v12 and v13.
- [x] 1.2 Record that the switched on "Default" cases fail without the
  `defaultHeaderType` mapping.

## 2. Implementation

- [x] 2.1 The constant, setting and site setting `renderContentElementHeader`
  (default `0`) and the `defaultHeaderType` mapping in both extensions.
- [x] 2.2 Wrap the `Header/All` line of the four templates in the switch.

## 3. Documentation

- [x] 3.1 The `Important` changelog entries in `Documentation/Changelog/2.4/`
  of both extensions, and the configuration sections.
- [x] 3.2 `docs/architecture/content-element-rendering.md`,
  `docs/testing/testing-helper.md`, the trait count in `AGENTS.md` and
  `docs/architecture/class-design.md`, the trait tables of
  `docs/development/monorepo-layout.md` and `docs/workflow/backporting.md`,
  and the index rows.

## 4. Definition of done

- [ ] 4.1 `Build/Scripts/runTests.sh -t 12 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 12` green.
- [ ] 4.2 The same for `-t 13` after its own `composerUpdate`.
- [ ] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 4.4 Commit as `[BUGFIX] ACE-729: <subject>` in TYPO3 Core format, and
  archive the change as the last commit of the pull request.
