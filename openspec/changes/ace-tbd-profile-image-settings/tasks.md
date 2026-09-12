## 1. Prerequisites

- [ ] 1.1 Confirm `ace-tbd-responsive-image-partial`,
  `ace-tbd-named-crop-variants` and `ace-tbd-item-and-list-partials` are
  merged, and verify the crop variant names and the partial arguments in the
  merged sources before writing any setting.
- [ ] 1.2 Verify that `plugin.tx_academicpersons.image.placeholder.default`
  is declared with the shipped placeholder SVG as its default; declare it
  here with exactly that default only if it is not, and never add a second
  placeholder setting.

## 2. Settings

- [ ] 2.1 Declare the remaining `plugin.tx_academicpersons.image.*` settings
  (crop variants and gender placeholders, no widths) in
  `Configuration/Sets/Full/settings.definitions.yaml` and the constants with
  identical defaults, and map them to `settings.image` in the shared setup;
  extend the site set functional test to assert the defaults reach the plugin
  settings, and show it fails with the setup mapping removed.

## 3. Templates

- [ ] 3.1 Render the item image through the responsive partial with the list or
  card settings; add a functional card test with `card.cropVariant = square`
  that asserts a processed file cropped to the square area, and show it fails
  against the unchanged partial.
- [ ] 3.2 Render the public profile image through the responsive partial with
  the detail crop variant and the `detail` preset; add a functional detail
  test asserting no source wider than 1200 pixels, and a card test asserting
  the sources of the `card` preset.
- [ ] 3.3 Render the placeholder for a profile without an image, with the
  gender-specific value first; add functional tests for the shipped
  placeholder without configuration, a configured default placeholder, the
  gender placeholder, an empty default placeholder (no image element) and an
  image excluded by the shown fields, and show the gender placeholder test
  fails against the unchanged partial.
- [ ] 3.4 Add a functional test with an undefined crop variant name that
  asserts the page renders the uncropped image.
- [ ] 3.5 Add functional tests for a placeholder given as an `EXT:` path of a
  fixture extension (rendered) and for an `EXT:` path to a missing file
  (the rendering fails with the `f:image` exception), on v13 and v14.

## 4. Documentation

- [ ] 4.1 Document the settings, the one placeholder format (`EXT:` path, a
  missing file fails), that widths are changed by overriding the preset
  section of the responsive image partial, and the unknown-name behaviour in
  the persons integrator documentation (`Documentation/Configuration/`) and
  in `Documentation/Templates/`.
- [ ] 4.2 Add `Documentation/Changelog/3.0/Feature-ProfileImageSettings.rst`
  from the template in `Build/Documentation/Templates/`.
- [ ] 4.3 Add the placeholder node rule (no value and children on one site
  settings node) to `docs/architecture/typoscript-and-site-sets.md`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack and verify the
  key with a GET request.
- [ ] 5.2 Rename the change to `ace-<NNN>-profile-image-settings` and verify
  `openspec validate` passes under the new name.
- [ ] 5.3 Commit as `[FEATURE] ACE-<NNN>: Configure profile image rendering`
  in TYPO3 Core format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13.
- [ ] 6.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit.
- [ ] 6.5 Archive the change as the last commit of the pull request.
