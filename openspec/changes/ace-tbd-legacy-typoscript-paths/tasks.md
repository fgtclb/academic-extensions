## 1. Tests first

- [ ] 1.1 Add a functional test per extension (precedent:
      `academic-jobs/Tests/Functional/SiteSet/SiteSetDeliveryTest.php`) that
      builds the frontend TypoScript once from a template record including
      `Full` and once from one including the 2.x path; assert both trees are
      equal and non-empty, for example `plugin.tx_academicbitejobs.view`, and
      record that the 2.x case fails on the current tree.
- [ ] 1.2 Add a case per extension that imports the 2.x `setup.typoscript` by
      file and asserts the plugin or content element configuration; record
      that it fails today.
- [ ] 1.3 Add a TCA assertion per extension that the `include_static_file`
      items contain the 2.x value; record that it fails today.

## 2. Implementation

- [ ] 2.1 Add the thin `setup.typoscript` and `constants.typoscript` to the
      2.x folders of bite_jobs, contacts4pages and persons_edit, the new
      `academic-study-plan/Configuration/TypoScript/Default/` folder, and
      the contacts4pages `include_static_file.txt`; verify 1.1 and 1.2 are
      green on v13 and v14.
- [ ] 2.2 Register the 2.x paths in each
      `Configuration/TCA/Overrides/sys_template.php` with a deprecated label
      and a comment in the style of academic_partners; verify 1.3.

## 3. Documentation

- [ ] 3.1 Extend `docs/architecture/typoscript-and-site-sets.md` with the
      2.x paths that are kept and why, and verify it keeps its `## See also`.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Deprecation-LegacyStaticTemplatePath.rst`
      to each of the four extensions from
      `Build/Documentation/Templates/Changelog-Deprecation.rst`, naming "All
      components" as the replacement and warning against combining the
      import with the set; update each `Documentation/Configuration` chapter.

## 4. Backport

- [ ] 4.1 Backport: separate change on branch `2` after a backport analysis
      (`docs/workflow/backporting.md`); the 2.4 restructure has the same dead
      paths, but the folders and sets there differ.

## 5. File the issue

- [ ] 5.1 File the ACE issue in YouTrack, rename the change to
      `ace-<NNN>-legacy-typoscript-paths`, and commit as
      `[TASK] ACE-<NNN>: Keep the 2.x TypoScript paths working` in TYPO3 Core
      format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14.
- [ ] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.3 `docs/` and the four extensions' `Documentation/` changelogs updated
      in the same change.
- [ ] 6.4 Archive the change as the last commit of the pull request.
