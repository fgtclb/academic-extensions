## 1. Tests first

- [ ] 1.1 Extend or add the functional plugin rendering tests of
      academic_partners (list, partnerships list and teaser), academic_programs
      (list), academic_projects (list) and academic_jobs (list) with an item
      that has an image; assert the `<picture>` wrapper and
      `type="image/webp"`, and record that they fail on the current templates.
- [ ] 1.2 Add frontend rendering tests for a doktype 20, 30 and 40 page with
      media; assert the `<picture>` with the detail preset, and record that
      they fail today.
- [ ] 1.3 Add an SVG logo case for the partner list items, the partnership
      items and the job list items; assert the original SVG file and no webp
      source. Assert that the partner list item renders the `logo` preset,
      not `card`, and show it red with `card`.

## 2. Implementation

- [ ] 2.1 Replace the nine image calls with the shared partial and its preset;
      verify 1.1 to 1.3 on v13 and v14.
- [ ] 2.2 Register the academic_base partials in the plugin TypoScript of the
      four extensions and in the page objects of partners, programs and
      projects at keys below every project slot; verify that a project
      override at the project slot still wins.
- [ ] 2.3 Check both development instances with the Bootstrap Package theme
      after `ddev composer install`: the page templates render, and no theme
      partial path is replaced.

## 3. Documentation

- [ ] 3.1 Update the `docs/` section on the shared partial with the adopting
      extensions and the `page.10` key.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Important-ImagesRenderAsPicture.rst`
      to each of the four extensions from
      `Build/Documentation/Templates/Changelog-Important.rst`, and update their
      `Documentation/Templates` chapters where they exist.

## 4. File the issue

- [ ] 4.1 File the ACE issue in YouTrack, rename the change to
      `ace-<NNN>-image-partial-adoption`, and commit as
      `[FEATURE] ACE-<NNN>: Render list and page images responsively` in
      TYPO3 Core format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14.
- [ ] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.3 `docs/` and the four extensions' `Documentation/` changelogs updated
      in the same change.
- [ ] 5.4 Archive the change as the last commit of the pull request.
