## 1. Tests

- [x] 1.1 Add a fixture package overriding only `icon` and `inlineIcon` of a
  base type with `useExisting: true`, and a loader unit test asserting the new
  icon and `inlineIcon: false` while `title`, `group` and `priority` keep the
  base values and the type keeps its position; shown red by replacing the
  merge with the override entry alone, and by moving an overridden type to the
  end.
- [x] 1.2 Add a fixture package redeclaring a base type without
  `useExisting`, and a loader unit test asserting that the keys it leaves out
  fall back to their defaults and that the type keeps its position; shown red
  by merging every redeclaration.
- [x] 1.3 Add loader unit tests for an override loaded before the package
  that declares the type, and for an override of a type an earlier package
  removed, both expecting "Category type does not exist for override."; shown
  red by skipping the check, and by ignoring the removal.
- [x] 1.4 Add a functional test for the `sys_category` type select: the item
  value is the bare identifier, the group is headed with its raw key rather
  than the title of the `groups:` section, and the type icon is keyed by the
  bare identifier; shown red by prefixing the value with the group, and by a
  labelled divider for the group.

## 2. Documentation page

- [x] 2.1 Verify every key and default against the category type model and
  the loader before writing it down.
- [x] 2.2 Extend `typo3-category-types/Documentation/Developers/CategoryTypes/Index.rst`:
  `useExisting` and `remove` in the key list, the icon-only override with the
  `inlineIcon` rule, the redeclaration rule, the load order rule with the
  exception message, the removal order and `suggest` for an optional
  extension, the extension key after an override, the unused `groups:`
  section and the identifier rule. `priority` stays documented as without
  effect.
- [x] 2.3 Link the override section from `Documentation/Developers/Icons/Index.rst`.
- [x] 2.4 No `Documentation/Changelog/3.0/` entry and no `docs/` change:
  nothing an installation or a contributor observes changes. The pull request
  says so.

## 3. File the issue

- [x] 3.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-category-types-yaml-docs`, point the references of the
  three follow-up changes at the page, and commit in TYPO3 Core format as
  `[TASK] ACE-<NNN>: Document category type overrides` - `[TASK]` rather than
  `[DOCS]`, because the commit adds tests.

## 4. Backport

- [ ] 4.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`); the loader is byte-identical there, `2`
  has no `inlineIcon`.

## 5. Definition of done

- [x] 5.1 `lintPhp` green.
- [x] 5.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [x] 5.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`.
- [x] 5.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.5 `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 5.6 Archive the change as the last commit of the pull request.
