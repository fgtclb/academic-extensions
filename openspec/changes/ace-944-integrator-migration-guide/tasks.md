## 1. Documentation

- [x] 1.1 Copy the three `Changelog/2.4/Important-PageTemplateNameIsSetExplicitly.rst`
  from `main` and compare them with `cmp`.
- [x] 1.2 Add "Installations outside these paths" to "Before you start" of
  `academic-base/Documentation/Upgrade/Index.rst`, without the 3.0 step, and
  compare it with `main` by `diff`.

## 2. Definition of done

- [x] 2.1 `lintPhp` green.
- [x] 2.2 After `-t 12 -s composerUpdate`: `cgl -n`, `phpstan` and `unit`
  green with `-t 12`. No `functional` run, the change has no runtime
  behaviour.
- [x] 2.3 The same with `-t 13` after its own `composerUpdate`.
- [x] 2.4 `lintMarkdown -n` and `checkRstRenderingAll` green (render output
  removed before the lint).
- [x] 2.5 Commit as `[DOCS] ACE-944: <subject>` in TYPO3 Core format.
- [ ] 2.6 Archive the change as the last commit of the pull request.
