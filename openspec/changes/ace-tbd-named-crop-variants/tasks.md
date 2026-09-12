## 1. Tests first

- [ ] 1.1 Read the default crop variant of the image manipulation element in
      the v13 and v14 vendor trees, and record in `design.md` whether it is
      identical on both.
- [ ] 1.2 Add a functional TCA test (loaded TCA needs the functional
      bootstrap) asserting the variant keys, their order and ratios for the
      profile `image` column and for `pages.media` of doktypes 20 and 30, that
      `default` equals the core default, and that the standard page type and
      doktype 40 are unchanged (only the core default); record that it fails
      on the current TCA, and show the doktype 40 assertion red by adding the
      three variants to it.
- [ ] 1.3 Add a FormEngine case for a doktype 30 page on v13 that asserts the
      core `overrideChildTca.types` entry is still present after the
      `columnsOverrides` merge.

## 2. Implementation

- [ ] 2.1 Add the crop variants to the profile TCA and to the page type TCA
      overrides of programs and projects; leave the partners TCA unchanged;
      verify 1.2 and 1.3 on v13 and v14.
- [ ] 2.2 Open the cropper for a profile image and a program page image in
      both development instances, and verify the variants are offered and a
      crop is stored.

## 3. Documentation

- [ ] 3.1 Add the crop variant names and ratios to the `docs/` section on
      images, shared with candidates `cross-cutting-01` and `-02`.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Feature-NamedCropVariants.rst` to
      academic_persons, academic_programs and academic_projects from
      `Build/Documentation/Templates/Changelog-Feature.rst`, with the TCEFORM
      example for hiding a variant; academic_partners gets no entry, as it
      changes nothing.

## 4. File the issue

- [ ] 4.1 File the ACE issue in YouTrack, rename the change to
      `ace-<NNN>-named-crop-variants`, and commit as
      `[FEATURE] ACE-<NNN>: Add named crop variants` in TYPO3 Core format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14.
- [ ] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.3 `docs/` and the three extensions' `Documentation/` changelogs updated
      in the same change.
- [ ] 5.4 Archive the change as the last commit of the pull request.
