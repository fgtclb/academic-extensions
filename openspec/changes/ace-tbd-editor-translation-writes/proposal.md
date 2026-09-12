## Why

A person editing the translation of their profile gets inconsistent results.
A changed profile field is stored on the translation, but a vita entry added
on the same page is stored as a default-language row, and the translation
does not get it until someone edits the default language. After any write on
a translation the editor also skips the profile update announcement, so slug
and translations are not synchronised. One project fixed this with an XCLASS;
three more run multilingual self-editing and are exposed.

## What Changes

- Step 1: every accepted write of the frontend editor of
  `academic_persons_edit` (`packages/fgtclb/academic-persons-edit`) made while
  editing a translation announces the update of the default-language profile,
  so the translations are synchronised as after a default-language write.
- Step 2: the structure of documents and contacts is default-language data.
  While a person edits a translation, adding, deleting and reordering them is
  refused, and the editor shows a hint to make these changes in the default
  language instead of the controls. Changing the text of an existing,
  translated entry keeps writing the translation row, as today. **Breaking**
  for installations that let people add entries on translations.
- A reproducing functional test comes first; the defect was found by reading
  the code.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons-edit/translation-editing`: what the frontend editor does
  with writes made while a person edits a translation of their profile.

### Modified Capabilities

None.

## Impact

- `academic_persons_edit`: the announcement after writes, and for step 2 the
  structural endpoints and the document and contact rendering on
  translations.
- Functional tests of translated editing; integrator documentation; 3.0
  changelog entries (`Important-` for step 1, `Breaking-` for step 2, since
  it restricts editing on translations).

## Non-goals

- The public profile display.
- Translating a document the person created in the default language.
- The backend.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-12`). Four of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-editor-translation-writes` when the issue is filed after
implementation.
