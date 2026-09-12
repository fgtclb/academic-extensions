## 1. Office hours

- [ ] 1.1 Register the `academic-persons-clock` icon and add the
  `detail.officeHours` label in English and German (one line per
  `source`/`target`, two-space indentation); verify the icon in the existing
  icon registration test, or a new one.
- [ ] 1.2 Render the office hours row in `Contact.html`; add a functional
  detail test with office hours that asserts the text and label, and show it
  fails against the unchanged partial.
- [ ] 1.3 Add functional detail tests for plain text with two lines, for
  CKEditor-shaped HTML, and for a value with a `<script>` element (not
  rendered).

## 2. Position line

- [ ] 2.1 Normalise `profile.details.position.fields` with the allowed values
  and ship the default `[position]`; add unit tests in
  `Tests/Unit/Settings/AcademicPersonsSettingsFactoryTest.php` for the
  default, an ordered list and an unknown value.
- [ ] 2.2 Add the function type name partial and render the configured fields
  in `Position.html`; add a functional detail test with a function type and
  no position, and show it fails today because nothing is rendered.
- [ ] 2.3 Add functional tests for the female, male and general name, and for
  a profile without gender.

## 3. Fields to show

- [ ] 3.1 Confirm `ace-tbd-contract-field-partial-defects` is merged, add
  `contracts.functionType` to the fields to show and render it in
  `Field.html`; add a functional card test selecting it, and show it fails
  without the item.

## 4. Documentation

- [ ] 4.1 Document `profile.details.position.fields` in
  `Documentation/Configuration/Sections/Index.rst`, and the office hours row
  in `Documentation/Templates/`.
- [ ] 4.2 Add `Documentation/Changelog/3.0/Feature-DetailOfficeHoursAndFunctionType.rst`.
- [ ] 4.3 Add the new layout key to the persons graph section of
  `docs/architecture/validation-settings.md`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack and verify the
  key with a GET request.
- [ ] 5.2 Rename the change to `ace-<NNN>-detail-contact-office-hours-function`
  and verify `openspec validate` passes under the new name.
- [ ] 5.3 Commit as `[FEATURE] ACE-<NNN>: Show office hours on the profile` in
  TYPO3 Core format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13.
- [ ] 6.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit.
- [ ] 6.5 Archive the change as the last commit of the pull request.
