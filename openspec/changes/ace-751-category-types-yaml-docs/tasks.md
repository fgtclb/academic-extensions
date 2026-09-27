## 1. Backport analysis

- [x] 1.1 Diff every file of the `main` change against this branch and check
  the core APIs the tests use on v12 and v13.

## 2. Tests

- [x] 2.1 Port the four loader unit tests and their three fixture packages
  without `inlineIcon`; shown red on v12 by the same four loader mutations as
  on `main`.
- [x] 2.2 Port the functional test for the type select, with the group heading
  assertion in a `Core12/` and a `Core13/` class; shown red on v12 by
  prefixing the item value with the group and by a labelled divider, and the
  `Core13/` class on v13 by the labelled divider.

## 3. Documentation

- [x] 3.1 Derive `Documentation/Developers/CategoryTypes/Index.rst` from the
  final `main` page without `inlineIcon`, and link the icon-only override from
  `Documentation/Developers/Icons/Index.rst`.
- [x] 3.2 No `Documentation/Changelog/2.4/` entry and no `docs/` change:
  nothing an installation or a contributor observes changes.

## 4. Definition of done

- [x] 4.1 `lintPhp` green.
- [x] 4.2 After `-t 12 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 12`.
- [x] 4.3 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [x] 4.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.5 Commit as `[TASK] ACE-751: Document category type overrides`.
- [ ] 4.6 Archive the change as the last commit of the pull request.
