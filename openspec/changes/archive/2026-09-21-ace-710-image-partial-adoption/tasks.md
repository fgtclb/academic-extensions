## 1. Tests first

- [x] 1.1 Extend or add the functional plugin rendering tests of
      academic_partners (list, partnerships list and teaser), academic_programs
      (list), academic_projects (list) and academic_jobs (list) with an item
      that has an image; assert the `<picture>` wrapper and
      `type="image/webp"`, and record that they fail on the current templates.
- [x] 1.2 Add frontend rendering tests for a doktype 20, 30 and 40 page with
      media; assert the `<picture>` with the detail preset, and record that
      they fail today.
- [x] 1.3 Add an SVG logo case for the partner list items, the partnership
      items and the job list items; assert the original SVG file and no webp
      source. Assert that the partner list item renders the `logo` preset,
      not `card`, and show it red with `card`.

## 2. Implementation

- [x] 2.1 Replace the nine image calls with the shared partial and its preset;
      verify 1.1 to 1.3 on v13 and v14.
- [x] 2.2 Register the academic_base partials in the plugin TypoScript of the
      four extensions and in the page objects of partners, programs and
      projects at keys below every project slot; verify that a project
      override at the project slot still wins.
- [x] 2.3 Prove that the extension keys of `page.10` replace no path of a
      Bootstrap Package shaped site package. Done as a test rather than by
      hand in the development instances: the page template fixtures carry a
      theme path at the key `0` and a project path at `1`, the shape that
      package has, and a probe renders the assembled array back.

## 3. Documentation

- [x] 3.1 Update the `docs/` section on the shared partial with the adopting
      extensions and the `page.10` keys.
- [x] 3.2 Add `Documentation/Changelog/3.0/Breaking-ImagesRenderAsPicture.rst`
      to each of the four extensions from
      `Build/Documentation/Templates/Changelog-Breaking.rst`, and update their
      `Documentation/Templates` chapters where they exist. A `Breaking-` entry
      rather than the `Important-` one this list first named: the markup
      changes for every installation, and a view with replaced partial root
      paths fails until it lists the academic_base path - which is the same
      reason ACE-646 shipped `Breaking-ProfileImagesRenderAsPicture.rst`, and
      the reason the commit carries `[!!!]`.

## 4. File the issue

- [x] 4.1 File the ACE issue in YouTrack, rename the change to
      `ace-710-image-partial-adoption`, and commit as
      `[!!!][FEATURE] ACE-710: Render images responsively` in TYPO3 Core
      format. The subject this list first named is 62 characters long, which
      the 52 of the TYPO3 rules do not allow.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14.
- [x] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.3 `docs/` and the four extensions' `Documentation/` changelogs updated
      in the same change.
- [x] 5.4 Archive the change as the last commit of the pull request.
