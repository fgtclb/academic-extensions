## 1. The shared partial

- [ ] 1.1 Add a functional test in academic_base that renders a fixture
      template through the partial with a fixture image file (precedent: the
      ViewHelper tests that render a fixture template through
      `ViewFactoryInterface`), covering each of the four presets (`teaser`
      included, which no upstream template uses), a named crop variant, SVG,
      placeholder, empty output, caption and copyright with and without
      EXT:filemetadata; break each branch of the partial once and record that
      its assertion goes red.
- [ ] 1.2 Add `academic-base/Resources/Private/Partials/Academic/Image.html`
      with one section per preset; verify 1.1 on v13 and v14.
- [ ] 1.3 Verify that the `runTests.sh` container processes webp on both
      versions, and record what the partial renders when `webp` is removed from
      `imagefile_ext`; if processing is unavailable in the container, settle
      the assertion strategy before continuing.
- [ ] 1.4 Add a functional test that registers the academic_base path at the
      chosen negative key and a project override at the project slot, and
      asserts that the override wins on v13 and v14; show it red with the key
      `5` of the first proposal.

## 2. Adoption in academic_persons

- [ ] 2.1 Add functional plugin rendering tests for the profile list (profile
      with image, without image, placeholder disabled, `showFields` without
      the image) and the profile detail; record that they fail on the current
      templates.
- [ ] 2.2 Switch `Partials/Profile/Item.html` and
      `Partials/Profile/PublicProfile/ProfileImage.html` to the partial, add
      `Resources/Public/Images/ProfilePlaceholder.svg` as the default of
      `settings.image.placeholder.default` (introduce only that key if
      `ace-tbd-profile-image-settings` has not landed; reuse it if it has,
      and never add a second placeholder setting), and register the
      academic_base partials in the persons,
      contacts4pages and persons_edit views; verify 2.1 on v13 and v14.
- [ ] 2.3 Add a contacts4pages rendering test for a listed profile with an
      image; show it red with the academic_base path removed from the contacts
      view (the partial cannot be resolved).

## 3. Documentation

- [ ] 3.1 Add an academic_base `Documentation/Templates/Index.rst` chapter
      declaring the partial and its arguments `@api`, link it from the
      `Documentation/Index.rst` toctree, and update the academic_persons
      `Documentation/Templates` chapter.
- [ ] 3.2 Add a section on the shared partial and its root path key to the
      matching `docs/` page, or a new page linked from its section
      `Index.md`.
- [ ] 3.3 Add `academic-base/Documentation/Changelog/3.0/Feature-ResponsiveImagePartial.rst`,
      and `Important-ProfileImagesRenderAsPicture.rst` to academic_persons and
      academic_contacts4pages, from `Build/Documentation/Templates/`.

## 4. File the issue

- [ ] 4.1 File the ACE issue in YouTrack, rename the change to
      `ace-<NNN>-responsive-image-partial`, and commit as
      `[FEATURE] ACE-<NNN>: Add a responsive image partial` in TYPO3 Core
      format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14.
- [ ] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.3 `docs/` and the three extensions' `Documentation/` changelogs
      updated in the same change.
- [ ] 5.4 Archive the change as the last commit of the pull request.
