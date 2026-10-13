## Why

The partnerships and the page contacts of a page are read with no language
constraint at all, and every row is overlaid on its own. A translation that is
attached to the default language page is therefore returned next to its
default record, so a visitor sees a partnership or a contact twice, and a
record of one language only shows in every language. On `main` the page
contacts were fixed by ACE-484 and the partnerships by ACE-430, archived there
as `2026-10-13-ace-430-partnerships-per-language`. This branch has neither.

## What Changes

- Each partnership and each page contact renders once per language: in the
  default language as its default record, in a translated language as its
  translation where one exists and as its default record where none exists. A
  record of one language only renders in that language, and one for all
  languages renders everywhere.
- A partnership that renders as its translation names its role in the
  visitor's language.
- The partnership list and teaser keep every partnership under its role
  heading in a translated language, also when the partnerships of one role are
  partly translated.
- The number of records a template counts agrees with the number it iterates.
- A frontend preview of hidden records keeps showing hidden partnerships and
  contacts.
- Rows already in the database stop rendering twice, without a cleanup.

## Capabilities

### New Capabilities

- `academic-partners/partnership-languages`: which partnerships a visitor sees
  per language.
- `academic-contact4pages/page-contact-languages`: which page contacts a
  visitor sees per language.

### Modified Capabilities

None.

## Impact

- `academic_partners` (`packages/fgtclb/academic-partners`): the repository
  that reads the partnerships of a page, the role groups of the partnership
  list and teaser, functional tests, a `Documentation/Changelog/2.4/`
  `Important` entry.
- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`): the
  repository that reads the contacts of a page, functional tests, a
  `Documentation/Changelog/2.4/` `Important` entry.
- `docs/architecture/translation-synchronization.md`.
- TYPO3 v12 and v13 render the same records after the change, and did before
  it as well. Both returned the duplicates.
- Frontend only. No schema change, no TCA change.

## Non-goals

- The DataHandler policy of ACE-484 that gives a contact of an untranslated
  page no translation. It builds on ACE-483, which this branch does not have.
  The maintainer decided to leave it out without an issue.
- An upgrade wizard for the copies. The maintainer decided against one on
  `main`.
- The identity map of the TYPO3 v12 and v13 data mapper, which hands the
  default language role to every partnership after an untranslated one with
  that role. Core behaviour, named in the changelog.
