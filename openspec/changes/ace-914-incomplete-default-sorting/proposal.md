## Why

The partner list and the partner map of `academic_partners`
(`packages/fgtclb/academic-partners`) and the program list of
`academic_programs` (`packages/fgtclb/academic-programs`) split their
"Default sorting" into a field and a direction. A value that is not exactly a
field and a direction stops the content element with a PHP error on every
request without a sorting of the visitor: an empty value, a field alone set
in TypoScript for an element that stores no sorting, a value written by an
import or a hand-written FlexForm.

This is the backport of ACE-914, made on `main` in pull request #985. There
the program list was already guarded by ACE-91, so ACE-914 only guarded the
partners. ACE-91 is not on this branch, so both factories get the guard.

## What Changes

- A default sorting that is not a field and a direction is ignored, and the
  element renders sorted by title, A to Z, the default of the extension.
- A value of more than two words, which applied its first two words before,
  is ignored as well. A field and a direction after a leading space, which
  fell back to title, A to Z, before, and a field and a direction separated
  by more than one space, which lost the direction before, are applied.
- A value of the "Default sorting" select and a sorting chosen by a visitor
  are applied as before.
- The behaviour is the same on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `academic-partners/list-sorting`: the ordering the partner list and the
  partner map start with, before a visitor chose one.

### Modified Capabilities

- `academic-programs/program-list-sorting`: a requirement is added for a
  default sorting that is not a field and a direction.

## Impact

- `academic_partners` and `academic_programs`: the demand factory of each
  extension, which turns the plugin settings into the sorting of the list.
- No database, TCA, FlexForm, template, label or dependency change.
- Tests: one unit test class per extension from `main`, the programs one
  without its pointer to a functional test this branch lacks.
- Documentation: one `2.4` changelog entry per extension, byte-identical to
  `main`. No manual page here describes the setting. The unit test class
  count in `docs/testing/unit-tests.md` is measured again.

## Non-goals

- The form fallback of ACE-91 on `main`, where a submitted filter without a
  sorting falls back to the configured default sorting. That is a feature of
  3.0 and not part of this fix.
- Validating the field and the direction beyond their count. An unknown part
  is still dropped by the demand while the known one applies, as for a
  sorting of the visitor.
- `academic_projects`, which never splits its default sorting and ignores a
  value it does not offer.

## Source

Backport of ACE-914 (pull request #985 on `main`), re-derived from the
backport analysis. `main` has no OpenSpec change for ACE-914, so this one is
written for this branch from the start.
