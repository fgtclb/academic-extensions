## Purpose

Defines what the frontend profile editor of `academic_persons_edit` does with
writes that a person makes while editing a translation of their profile, on
TYPO3 v13 and v14.

## ADDED Requirements

### Requirement: Writes on a translation synchronise the profile
The system SHALL synchronise the translations and the slug of a profile after
every accepted write made while the person edits a translation of that
profile, exactly as after a write made in the default language.

#### Scenario: Pending default-language contract reaches the translation
- **WHEN** the default-language profile has a contract without a language-1
  translation, and the person saves any change while editing the language-1
  translation
- **THEN** after the response the language-1 profile carries a translation of
  that contract

#### Scenario: Write in the default language
- **WHEN** the person saves a change while editing the default language
- **THEN** the translations are synchronised as before the change

### Requirement: Profile fields changed on a translation stay on the translation
The system SHALL store a profile field changed while editing a translation on
that translation, and the following synchronisation SHALL NOT overwrite it
with the default-language value unless the field is not translatable.

#### Scenario: Translated website
- **WHEN** the person changes the website while editing the language-1
  translation and the website is translatable
- **THEN** the language-1 profile shows the new website and the
  default-language profile keeps its own

### Requirement: Entries are added, removed and ordered in the default language
The system SHALL refuse adding, deleting and reordering documents and
contacts while the person edits a translation of the profile, and SHALL show
a hint to make these changes in the default language instead of the
controls for them.

#### Scenario: Adding a vita entry on a translation
- **WHEN** the person submits a new vita entry while editing the language-1
  translation
- **THEN** no entry is stored and the editor shows the hint to add it in the
  default language

#### Scenario: Deleting a contact on a translation
- **WHEN** the person requests to delete an e-mail address while editing the
  language-1 translation
- **THEN** the e-mail address stays in every language

#### Scenario: Controls on a translation
- **WHEN** the person opens the language-1 translation of the profile in the
  editor
- **THEN** the document and contact sections offer no add, delete or sort
  controls and show the hint instead

### Requirement: Text of existing entries is edited on the translation
The system SHALL store a text change to an existing, translated document or
contact made while editing a translation on that translation, and SHALL keep
the default-language entry unchanged.

#### Scenario: Translated vita text
- **WHEN** the person changes the text of a translated vita entry while
  editing the language-1 translation
- **THEN** the language-1 profile shows the new text and the default-language
  entry keeps its own
