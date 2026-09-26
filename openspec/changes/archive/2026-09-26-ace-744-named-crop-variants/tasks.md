## 1. Tests first

- [x] 1.1 Read the default crop variant of the image manipulation element in
      the v13 and v14 vendor trees, and record in `design.md` whether it is
      identical on both.
- [x] 1.2 Add functional tests that compile the record form and read the
      variants the cropper offers - keys, order and ratios - for the profile
      `image` column and for `pages.media` of doktypes 20 and 30; that
      `default` equals the built-in variant of the installed core; that a
      stored crop in no offered ratio keeps its area; and that the standard
      page type and doktype 40 offer only the built-in variant. Recorded red on
      the current TCA on v13 and v14, and the doktype 40 test red with the
      three variants added to it.
- [x] 1.3 Assert for doktypes 20 and 30 that the page type adds nothing but
      the crop variants to the media field, which on v13 includes the core
      `overrideChildTca.types` entry after the `columnsOverrides` merge.

## 2. Implementation

- [x] 2.1 Add the crop variants to the profile TCA and to the page type TCA
      overrides of programs and projects; leave the partners TCA unchanged;
      verify 1.2 and 1.3 on v13 and v14.
- [x] 2.2 Open the cropper for a profile image and a program page image in
      both development instances, and verify the variants are offered and a
      crop is stored.

## 3. Documentation

- [x] 3.1 Add `docs/architecture/crop-variants.md` with the names and
      ratios, linked from the architecture index and from the shared partials
      page, and the new trait to `docs/testing/testing-helper.md`.
- [x] 3.2 Add `Documentation/Changelog/3.0/Feature-NamedCropVariants.rst` to
      academic_persons, academic_programs and academic_projects from
      `Build/Documentation/Templates/Changelog-Feature.rst`, with the TCA
      example for disabling a variant (page TSconfig reaches every image below
      the page and is warned against), and a section on the variants to their
      configuration chapters; academic_partners gets no entry, as it changes
      nothing.

## 4. File the issue

- [x] 4.1 File the ACE issue in YouTrack, rename the change to
      `ace-<NNN>-named-crop-variants`, and commit as
      `[FEATURE] ACE-<NNN>: Add named crop variants` in TYPO3 Core format.
      Filed as ACE-744 (`[3.x]`, Story, Version 3.0.0, subtask of ACE-10,
      relates to ACE-572 and ACE-263).

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14.
- [x] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.3 `docs/` and the three extensions' `Documentation/` changelogs updated
      in the same change.
- [x] 5.4 Archive the change as the last commit of the pull request.
