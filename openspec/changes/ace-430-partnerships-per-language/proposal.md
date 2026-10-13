## Why

The partnerships of a partner page are read with no language constraint at
all, and every row is overlaid on its own. A translated partnership that
still points at the default language page is therefore returned next to its
default record, so a visitor sees the partnership twice, on the partner page
and in the partnership list and teaser. Localizing a partner role created
such rows until ACE-874, and localizing a partnership on its own in the List
module still does. The page contacts had the same defect and were fixed on
this branch by ACE-484. The partnerships were not.

## What Changes

- Each partnership renders once per language: in the default language as its
  default record, in a translated language as its translation where one
  exists and as its default record where none exists. A partnership that
  exists only in the translated language renders there and nowhere else, and
  one valid in all languages renders everywhere.
- The role a translated partnership shows is named in the visitor's language.
- The partnership list and teaser keep every partnership under its role
  heading in a translated language, also when the partnerships of one role
  are partly translated. They used to keep only the ones whose role object
  came last, on TYPO3 v13 always and on v14 once this change returns the
  untranslated partnerships there as well.
- Which partnerships render no longer depends on the overlay setting of the
  site, and TYPO3 v13 and v14 render the same. For the page contacts, whose
  query had the same shape, v14 dropped untranslated records in a translated
  language where v13 showed them.
- The number of partnerships a template counts agrees with the number it
  iterates.
- Rows already in the database stop rendering twice, without a cleanup.

**Maintainer's decision**: no upgrade wizard removes the copies created
before ACE-874. See `design.md`.

## Capabilities

### New Capabilities

- `academic-partners/partnership-languages`: which partnerships a visitor
  sees on a partner page and in the partnership list and teaser, per
  language.

### Modified Capabilities

None.

## Impact

- `academic_partners` (`packages/fgtclb/academic-partners`): the repository
  that reads the partnerships of a page, the role groups of the partnership
  list and teaser (controller, page data processor, two templates), their
  functional tests, a `Documentation/Changelog/3.0/` `Important` entry.
- `docs/architecture/translation-synchronization.md`: the read side section
  names the partnerships next to the page contacts.
- Frontend only. No schema change, no TCA change.
- TYPO3 v13 and v14 render the same after the change. Before it they differ
  in a translated language.

## Backport

ACE-430 is `[3.x][2.x]`. Branch `2` gets a change of its own, derived from a
backport analysis: the partnerships, byte-identical there, and the repository
half of ACE-484 for the page contacts, which never reached `2`. Not the
DataHandler hook of ACE-484, which builds on ACE-483 of `main`. Hidden
records are handled differently there, and the language handling has to be
measured on TYPO3 v12.

## Non-goals

- A DataHandler policy for partnerships like the one ACE-484 added for page
  contacts. ACE-874 keeps a role localization from creating the copies, and a
  partnership localized on its own is a legitimate translation that now
  renders once.
- Partnerships created in a translated language on the translation of the
  partner page, without a default record. They are not read before or after
  this change.
