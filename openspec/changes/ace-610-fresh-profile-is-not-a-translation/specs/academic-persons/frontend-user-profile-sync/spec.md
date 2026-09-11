## ADDED Requirements

### Requirement: A profile created from a frontend user is translated
The system SHALL treat a profile that `academic:createprofiles` creates from
a frontend user as a default-language profile. When `academic_persons_edit`
is installed and languages are configured in its option
`profile.allowedLanguages`, the translations of the new profile SHALL be
created in the same run, on TYPO3 v13 and v14.

#### Scenario: New profile with one allowed language
- **WHEN** `academic:createprofiles` creates a profile for a frontend user
  and `profile.allowedLanguages` lists a language the site offers
- **THEN** a translation of that profile exists in that language when the
  command has finished

#### Scenario: New profile without allowed languages
- **WHEN** `academic:createprofiles` creates a profile and
  `profile.allowedLanguages` is empty
- **THEN** only the default-language profile exists

### Requirement: Profiles loaded from the database keep their language status
The system MUST continue to report a profile loaded in a translation as a
translation, and a profile loaded in the default language as a
default-language profile.

#### Scenario: Translated profile is updated in the frontend editor
- **WHEN** a frontend user saves a profile in a translated language
- **THEN** the translation synchronisation is not started from that
  translation, exactly as before
