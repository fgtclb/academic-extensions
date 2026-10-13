## Purpose

Defines which contacts of a page a visitor sees in each language of the site.

## ADDED Requirements

### Requirement: Each page contact renders once in every language

The contacts attached to a page SHALL render each contact exactly once in
every language of the site. This applies to TYPO3 v12 and v13 alike, and the
two MUST render the same contacts.

#### Scenario: A translated contact in the default language

- **WHEN** a visitor views a page in the default language and one of its
  contacts has a translation
- **THEN** that contact renders once, as its default language record

#### Scenario: A translated contact in its language

- **WHEN** a visitor views a page in a translated language and one of its
  contacts has a translation in that language
- **THEN** that contact renders once, as its translation

#### Scenario: An untranslated contact in a translated language

- **WHEN** a visitor views a page in a translated language and one of its
  contacts has no translation in that language
- **THEN** that contact renders once, as its default language record

#### Scenario: A contact that exists in one language only

- **WHEN** a contact exists only in a translated language
- **THEN** it renders in that language, and in no other language

#### Scenario: A contact valid in all languages

- **WHEN** a contact is set to all languages
- **THEN** it renders once in every language

#### Scenario: A language without any translated contact

- **WHEN** a visitor views a page in a language into which none of its
  contacts is translated
- **THEN** the page renders the default language contacts, and no contact of
  another language

### Requirement: Hidden contacts follow the plugin option and the preview

A hidden contact SHALL render only when the content element shows hidden
records, or when a frontend preview shows hidden records, in each language as
described above.

#### Scenario: Hidden records not shown

- **WHEN** a contact is hidden and neither the content element nor a preview
  shows hidden records
- **THEN** the contact renders in no language

#### Scenario: A preview of hidden records

- **WHEN** a backend user previews a page with hidden records shown and one
  of its contacts is hidden
- **THEN** the contact renders

### Requirement: Counting the page contacts agrees with listing them

The number of contacts a template counts SHALL equal the number of contacts it
renders, in every language.

#### Scenario: A template counts and lists the contacts

- **WHEN** an integrator's template shows the number of contacts of a page
  and lists them
- **THEN** both name the same number in every language

### Requirement: Existing contact translations need no cleanup

Translations created before this behaviour SHALL render once per language
without any change to the database.

#### Scenario: Updating an installation with duplicated translations

- **WHEN** an installation whose database holds a translation of a contact
  next to its default record updates
- **THEN** every page renders each contact once per language, without an
  upgrade wizard or a manual cleanup
