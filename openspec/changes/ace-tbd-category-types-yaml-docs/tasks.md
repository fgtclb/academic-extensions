## 1. Test

- [ ] 1.1 Add a fixture package overriding only `icon` and `inlineIcon` of a
  base type with `useExisting: true`, and a loader unit test asserting the new
  icon and `inlineIcon: false` while `title` and `group` keep the base values;
  shown red by replacing the merge with the override entry alone.

## 2. Documentation page

- [ ] 2.1 Verify every key and default against the category type model and
  the loader before writing it down.
- [ ] 2.2 Create `typo3-category-types/Documentation/Developers/CategoryTypesYaml/Index.rst`
  with the key reference, the `groups:` section, the icon-only override,
  relabel and removal examples, the load order rule and the current limits,
  and add it to the toctree of `Documentation/Developers/Index.rst`. The
  `priority` key is documented as read but without effect;
  `ace-tbd-category-type-priority-order`, which lands after this change,
  rewrites that paragraph.
- [ ] 2.3 Link the page from `Documentation/Developers/Icons/Index.rst`.
- [ ] 2.4 No `Documentation/Changelog/3.0/` entry and no `docs/` change:
  nothing an installation or a contributor observes changes. The pull request
  says so.

## 3. File the issue

- [ ] 3.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-category-types-yaml-docs`, and commit in TYPO3 Core
  format as `[DOCS] ACE-<NNN>: Document CategoryTypes.yaml keys`.

## 4. Backport

- [ ] 4.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`); the loader behaves the same there.

## 5. Definition of done

- [ ] 5.1 `lintPhp` green.
- [ ] 5.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 5.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`.
- [ ] 5.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.5 `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 5.6 Archive the change as the last commit of the pull request.
