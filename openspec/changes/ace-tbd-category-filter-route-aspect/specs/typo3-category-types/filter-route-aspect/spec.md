## Purpose

Gives integrators a routing aspect that turns category filter values into
readable, translated URL segments that still resolve after a category is
renamed.

## ADDED Requirements

### Requirement: Filter values generate readable segments

The aspect SHALL generate, for a category uid, the segment
`<title-slug>-<uid>`, where the slug is derived from the category title in
the language of the generated URL. A comma separated list of uids SHALL
generate the segments of each uid in list order, joined by commas.

#### Scenario: One category in German

- **WHEN** a URL is generated in German for the category with uid 12 whose
  German title is "Europa"
- **THEN** the segment is `europa-12`

#### Scenario: Two categories in English

- **WHEN** a URL is generated in English for the uids `12,31` titled "Europe"
  and "University"
- **THEN** the segment is `europe-12,university-31`

### Requirement: Segments resolve by uid only

The aspect SHALL resolve a segment by reading the trailing uid of each
comma separated part. A part SHALL resolve only when a category with that uid
exists and belongs to the configured category group; otherwise the whole
segment MUST NOT resolve.

#### Scenario: Category was renamed

- **WHEN** a visitor opens `europa-12` after category 12 was renamed to
  "Europäische Union"
- **THEN** the segment resolves to uid 12

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
