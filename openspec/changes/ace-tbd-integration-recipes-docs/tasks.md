## 1. Wizard behaviour

- [ ] 1.1 Add a functional backend test in `academic-base/Tests/Functional/`
  that builds the new content element wizard and asserts the position of the
  academic group and the order of its items, on v13 and v14; shown red by
  removing `after = special` from
  `academic-base/Configuration/TSconfig/CTypeGroup/page.tsconfig`.
- [ ] 1.2 Extend the test with the relabel, position and hide TSconfig the
  recipe will show, and record which of them take effect on each version.

## 2. Recipes

- [ ] 2.1 Create `academic-base/Documentation/Integration/Index.rst` and add
  it to the toctree of `academic-base/Documentation/Index.rst`.
- [ ] 2.2 Write the wizard recipe from the findings of 1.2 only: group
  position, relabelling and item order, without a numbering scheme (left to
  ACE-286).
- [ ] 2.3 Write the EXT:solr recipe for EXT:solr 13.x: profile index queue
  with the text columns taken from the profile TCA, the detail link through
  the detail plugin's arguments, and page queues for doktypes 20 and 30; name
  the exact EXT:solr release and have it reviewed against a project
  installation. No v14 section until an EXT:solr release for v14 is verified.
- [ ] 2.4 Once the maintainer has supplied the link to the format
  documentation of `b13/permission-sets`, write one example per extension
  with its tables, fields and plugins, taken from the TCA.
- [ ] 2.5 Link the section from the manuals of `academic_persons`,
  `academic_programs` and `academic_projects`.
- [ ] 2.6 No `Documentation/Changelog/3.0/` entry and no `docs/` change:
  nothing an installation or a contributor observes changes. The pull
  request says so.

## 3. File the issue

- [ ] 3.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-integration-recipes-docs`, and commit in TYPO3 Core
  format as `[DOCS] ACE-<NNN>: Add integration recipes`.

## 4. Definition of done

- [ ] 4.1 `lintPhp` green.
- [ ] 4.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 4.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`.
- [ ] 4.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 4.5 `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 4.6 Archive the change as the last commit of the pull request.
