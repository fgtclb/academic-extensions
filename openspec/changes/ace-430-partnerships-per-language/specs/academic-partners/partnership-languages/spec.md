## Purpose

Defines which partnerships a visitor sees on a partner page and in the
partnership list and teaser, in each language of the site.

## ADDED Requirements

### Requirement: Each partnership renders once in every language

The partnerships attached to a partner page SHALL render each partnership
exactly once in every language of the site, on the partner page itself and in
the partnership list and teaser. This applies to TYPO3 v12 and v13 alike, and
the two MUST render the same partnerships.

#### Scenario: A translated partnership in the default language

- **WHEN** a visitor views a partner page in the default language and one of
  its partnerships has a translation
- **THEN** that partnership renders once, as its default language record

#### Scenario: A translated partnership in its language

- **WHEN** a visitor views a partner page in a translated language and one of
  its partnerships has a translation in that language
- **THEN** that partnership renders once, as its translation

#### Scenario: An untranslated partnership in a translated language

- **WHEN** a visitor views a partner page in a translated language and one of
  its partnerships has no translation in that language
- **THEN** that partnership renders once, as its default language record

#### Scenario: A translation that points at the default partner page

- **WHEN** a partnership was translated by localizing it on its own, or by
  localizing its role in an earlier release, so that the translation is
  attached to the default language partner page
- **THEN** the partnership renders once in the default language and once in
  the translated language, never twice on one page

#### Scenario: A partnership that exists in one language only

- **WHEN** a partnership exists only in a translated language and is attached
  to the default language partner page
- **THEN** it renders on that page in that language, and in no other language

#### Scenario: A partnership valid in all languages

- **WHEN** a partnership is set to all languages
- **THEN** it renders once in every language

#### Scenario: A language without any translated partnership

- **WHEN** a visitor views a partner page in a language into which none of
  its partnerships is translated
- **THEN** the page renders the default language partnerships, and no
  partnership of another language

#### Scenario: A hidden partnership

- **WHEN** a partnership is hidden
- **THEN** it renders in no language

### Requirement: A translated partnership names its role in its language

A partnership that renders as its translation SHALL show its role in the
language of that translation, and in the default language where the role has
no translation into it. This holds unless an untranslated partnership with the
same role renders before it on the same page. A
partnership that renders as its default language record SHALL show the role
as its default language record names it.

#### Scenario: A translated partnership with a translated role

- **WHEN** a visitor views a partner page in a translated language, a
  partnership has a translation in that language and its role is translated
  into it
- **THEN** the partnership shows the translated role name, once

#### Scenario: A translated partnership with an untranslated role

- **WHEN** a partnership renders as its translation and its role has no
  translation into that language
- **THEN** the partnership shows the default language role name

#### Scenario: An untranslated partnership

- **WHEN** a partnership has no translation into the language of the page
- **THEN** it shows the role name of its default language record

#### Scenario: A translated partnership after an untranslated one

- **WHEN** a visitor views a partner page in a translated language, and a
  partnership that renders as its translation follows an untranslated
  partnership with the same role
- **THEN** it shows the default language role name, as the untranslated one
  does, on TYPO3 v12 and v13 alike

### Requirement: Every partnership stays in its role group

The partnership list and teaser SHALL render every partnership under the
heading of its role in every language, also when one page holds partnerships
of one role that render as their translation next to ones that render as
their default language record. The heading SHALL name the role in the
visitor's language when one partnership of the group carries the translated
role.

#### Scenario: Translated and untranslated partnerships share a role

- **WHEN** a visitor views the partnership list or teaser in a translated
  language, and of two partnerships with the same role one has a translation
  and the other has none
- **THEN** both render once, under one heading that names the role in the
  visitor's language

### Requirement: The overlay setting of the site does not change the partnerships

Which partnerships render in a translated language SHALL NOT depend on the
fallback setting of that site language. An untranslated partnership renders as
its default language record whether the language falls back to the default
language or not.

#### Scenario: A language without fallback

- **WHEN** a site language is configured without fallback and a partnership of
  the partner page has no translation into it
- **THEN** the partnership renders as its default language record

### Requirement: A preview of hidden records shows hidden partnerships

A frontend preview that shows hidden records SHALL show a hidden partnership
of the page, in each language as described above.

#### Scenario: A preview of hidden records

- **WHEN** a backend user previews a partner page with hidden records shown
  and one of its partnerships is hidden
- **THEN** the partnership renders, as its translation where it has one

### Requirement: Counting the partnerships agrees with listing them

The number of partnerships a template counts SHALL equal the number of
partnerships it renders, in every language.

#### Scenario: A template counts and lists the partnerships

- **WHEN** an integrator's template shows the number of partnerships of a page
  and lists them
- **THEN** both name the same number in every language

### Requirement: Existing translations need no cleanup

Translations created before this behaviour, the copies a role localization
attached to the default language partner page included, SHALL render once per
language without any change to the database.

#### Scenario: Updating an installation with copies from an earlier release

- **WHEN** an installation whose database holds such copies updates
- **THEN** every partner page renders each partnership once per language,
  without an upgrade wizard or a manual cleanup
