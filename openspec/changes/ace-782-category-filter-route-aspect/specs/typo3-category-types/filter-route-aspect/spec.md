## Purpose

Gives integrators a routing aspect that turns category filter values into
readable, translated URL segments that still resolve after a category is
renamed.

## ADDED Requirements

### Requirement: Filter values generate readable segments

The aspect SHALL generate, on TYPO3 v13 and v14, the segment
`<title-slug>-<uid>` for a category uid, where the slug is derived from the
category title in the language of the generated URL. A comma separated list
of uids SHALL generate the segments of each uid in list order, joined by
commas.

#### Scenario: One category in German

- **WHEN** a URL is generated in German for the category with uid 12 whose
  German title is "Europa"
- **THEN** the segment is `europa-12`

#### Scenario: Two categories in English

- **WHEN** a URL is generated in English for the uids `12,31` titled "Europe"
  and "University"
- **THEN** the segment is `europe-12,university-31`

#### Scenario: A list URL without cache hash

- **WHEN** a filtered URL is generated for a list plugin that excludes its
  demand from the cache hash, as the partner, project and program lists do
- **THEN** the URL is the readable path without a `cHash` argument
- **AND** opening it renders the filtered list

#### Scenario: A value that cannot be mapped

- **WHEN** a URL is generated for a filter value that names a hidden, deleted,
  unknown or foreign category, or that is no list of uids
- **THEN** no segment is generated and the link keeps the filter as a query
  argument

### Requirement: Segments resolve by uid only

The aspect SHALL resolve a segment by reading the trailing uid of each
comma separated part, on TYPO3 v13 and v14. A part SHALL resolve only when a
visible category of the default language or of all languages has that uid and
belongs to the configured category group. Otherwise the whole segment MUST NOT
resolve.

#### Scenario: Category was renamed

- **WHEN** a visitor opens `europa-12` after category 12 was renamed to
  "Europäische Union"
- **THEN** the segment resolves to uid 12

#### Scenario: Hidden, deleted or translated record

- **WHEN** a visitor opens a segment whose uid is a hidden or deleted
  category, or the translation record of a category
- **THEN** the route does not match

#### Scenario: Foreign or unknown category

- **WHEN** a visitor opens a segment whose uid belongs to another category
  group or does not exist
- **THEN** the route does not match and the page answers as for any unknown
  URL

### Requirement: The empty value has a localised token

The aspect SHALL generate a configurable token for an empty filter value,
chosen by the locale of the site language, and SHALL resolve that token back
to an empty value.

#### Scenario: No filter in German

- **WHEN** a URL without a filter value is generated in German with the
  token map `de_DE.*: alle` and `en_US.*: all`
- **THEN** the segment is `alle`
- **AND** opening `alle` resolves to no filter

### Requirement: Two categories with the same title stay distinct

Because every part carries its uid, two categories with the same title SHALL
generate different segments and resolve to their own uid.

#### Scenario: Duplicate titles

- **WHEN** two categories in the group are both titled "Berlin"
- **THEN** their segments differ in the uid and each resolves to its own
  category
