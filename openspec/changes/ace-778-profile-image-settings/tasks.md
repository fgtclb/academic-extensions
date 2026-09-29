## 1. Prerequisites

- [x] 1.1 Confirm `ace-646-responsive-image-partial`,
  `ace-744-named-crop-variants` and `ace-716-item-and-list-partials` are
  merged, and verify the crop variant names and the partial arguments in the
  merged sources before writing any setting.
- [x] 1.2 Verify that `plugin.tx_academicpersons.image.placeholder.default`
  is declared with the shipped placeholder SVG as its default. Declare it
  here with exactly that default only if it is not, and never add a second
  placeholder setting.

## 2. Settings

- [x] 2.1 Declare the remaining `plugin.tx_academicpersons.image.*` settings
  (crop variants and gender placeholders, no widths) in
  `Configuration/Sets/Full/settings.definitions.yaml` and the constants with
  identical defaults, and map them to `settings.image` in the shared setup.
  Extend the site set test that pins the declared defaults, add one that
  compares every declared default with its constant, and show the rendering
  tests below fail with the setup mapping removed.
- [x] 2.2 Map the list crop variant and the gender placeholders into the
  plugin settings of the contacts element of `academic_contacts4pages`, next
  to the placeholder it maps today.

## 3. Templates

- [x] 3.1 Pass `imageView: 'card'` from the card template through
  `Profile/List/Items` to the item, and render the item image with
  `settings.image.{imageView}.cropVariant`, `list` when no view is given. Add
  functional tests: the card with `card.cropVariant = square` renders the
  square crop, a list with the same configuration keeps the `default` crop,
  and `list.cropVariant = square` crops the list. Show the card test fails
  against the unchanged partial.
- [x] 3.2 Render the public profile image with the detail crop variant, and
  add a functional detail test with `detail.cropVariant = portrait` that
  asserts the cropped image and the sources of the `detail` preset, and show
  it fails against the unchanged partial.
- [x] 3.3 Render the gender placeholder for a profile without an image, with
  the default placeholder as fallback. Add functional tests for the
  placeholder of each of `mr`, `ms` and `diverse`, a gender without a
  placeholder, a profile without a gender, an empty default with a gender
  placeholder, and the empty alternative text.
  Show the gender placeholder tests fail against the unchanged partial, and
  each gender case with its mapping line broken.
- [x] 3.4 Add a functional test with an undefined crop variant name that
  asserts the page renders the uncropped image. It pins core behaviour this
  change keeps and does not implement, so it cannot be broken from here.
  The fixture stores a `default` crop smaller than the image, so the test
  would fail if the name fell back to it or to any other stored crop.
- [x] 3.5 Add functional tests for a placeholder given as an `EXT:` path of a
  fixture extension (rendered) and for an `EXT:` path to a missing file
  (the rendering fails with the `f:image` exception naming the path), on v13
  and v14. Both pass before this change as well. They pin the behaviour of
  the existing default placeholder setting and of the responsive partial,
  which this change documents and the gender placeholders share.
- [x] 3.6 Add a functional test for the contacts element with the list crop
  variant and the placeholder of each gender, and show it fails without the
  mapping of 2.2.

## 4. Documentation

- [x] 4.1 Document the settings, which element reads which crop variant, the
  one placeholder format (`EXT:` path, a missing file fails), that widths are
  changed by overriding the preset section of the responsive image partial,
  and the unknown-name behaviour in the persons documentation
  (`Documentation/Configuration/` and `Documentation/Templates/`).
- [x] 4.2 Add `Documentation/Changelog/3.0/Feature-ProfileImageSettings.rst`
  from the template in `Build/Documentation/Templates/`, naming the
  `imageView` argument that a copied `Card.html`, `Profile/List/Items.html`
  or `Profile/Item.html` lacks.
- [x] 4.3 Add the placeholder node rule (no value and children on one site
  settings node) to `docs/architecture/typoscript-and-site-sets.md`, and the
  view argument to `docs/architecture/shared-partials.md`.

## 5. File the issue

- [x] 5.1 After implementation, file the ACE issue in YouTrack and verify the
  key with a GET request.
- [x] 5.2 Rename the change to `ace-<NNN>-profile-image-settings` and verify
  `openspec validate` passes under the new name.
- [x] 5.3 Commit as `[FEATURE] ACE-<NNN>: Configure profile image rendering`
  in TYPO3 Core format.

## 6. Definition of done

- [x] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13.
- [x] 6.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit.
- [ ] 6.5 Archive the change as the last commit of the pull request.
