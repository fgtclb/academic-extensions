## 1. Tests first

- [ ] 1.1 Add frontend rendering tests for each of the thirteen plugin views
      with the header "Hello" in layout 0 and in layout 100 (precedent: the
      academic_jobs plugin tests); assert the heading and its absence, and
      record that the layout 0 cases fail on the current templates.
- [ ] 1.2 Add cases for the contacts list and the partnerships list and teaser
      with a subheader and two roles; assert the subheader appears once, and
      record that it fails after 2.2 without 2.3.
- [ ] 1.3 Run the layout 0 cases on v14 without the `record` assignment and
      record the failure, which proves the controller part is needed there.

## 2. Implementation

- [ ] 2.1 Require `typo3/cms-fluid-styled-content` in the five
      `composer.json` and `ext_emconf.php` files, and register its partial
      path below every project slot; verify a project override of
      `Header/All` at the project slot still wins.
- [ ] 2.2 Assign `record` in the seven controllers and render `Header/All`
      in the thirteen templates; verify 1.1 and 1.3 on v13 and v14.
- [ ] 2.3 Stop passing `data.subheader` to the role headings; verify 1.2.

## 3. Documentation

- [ ] 3.1 Add the plugin header rule to the `docs/` page on frontend
      templates, or a new page linked from its section `Index.md`.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Breaking-PluginsRenderContentElementHeader.rst`
      to the five extensions from
      `Build/Documentation/Templates/Changelog-Breaking.rst`: the markup before
      and after, the affected installations (a header rendered outside the
      plugin template), the migration (remove it, or header layout "Hidden")
      and the new requirement on `typo3/cms-fluid-styled-content`.

## 4. File the issue

- [ ] 4.1 File the ACE issue in YouTrack, rename the change to
      `ace-<NNN>-plugin-content-element-header`, and commit as
      `[!!!][FEATURE] ACE-<NNN>: Render the header in every plugin` in TYPO3
      Core format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14.
- [ ] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.3 `docs/` and the five extensions' `Documentation/` changelogs updated
      in the same change.
- [ ] 5.4 Archive the change as the last commit of the pull request.
