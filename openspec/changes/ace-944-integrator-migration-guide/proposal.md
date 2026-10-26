## Why

The upgrade chapters of ACE-910 give every manual the paths 2.3 to 2.4, 2.4
to 3.0 and 2.3 to 3.0, and they cover most of what the analysed projects ran
into. Three things those chapters still leave to the integrator:

- The profile editor of 3.0 replaces the whole form flow of 2.x. Its upgrade
  pages say to delete an override of a removed file and "re-apply what the
  project still wants", but most 2.x overrides of the analysed projects only
  removed buttons, fields or the synchronisation toggle, and 3.0 does each of
  those by a setting. Nothing maps one to the other.
- An installation on TYPO3 v12, on a release older than 2.3, on an unreleased
  revision or on a `2.0.x-dev` constraint has no path in the chapters.
- The page template name the extensions set since 2.4 is overwritten by
  TypoScript parsed after theirs. The changelog entry says so in general
  terms, but not that a static template of the Bootstrap Package is always
  parsed after a site set (ACE-601), which is the common case on TYPO3 v13.

## What Changes

- `academic_persons_edit` (`packages/fgtclb/academic-persons-edit`): a section
  in the upgrade pages 2.4 to 3.0 and 2.3 to 3.0 that maps each intent of a
  2.x template override to its 3.0 setting, and names what has no setting.
- `academic_base` (`packages/fgtclb/academic-base`): the "Before you start"
  part of its upgrade chapter gains the installations outside the documented
  paths, and the upgrade chapter of every other manual links it.
- `academic_programs`, `academic_partners` and `academic_projects`
  (`packages/fgtclb/academic-programs`, `academic-partners`,
  `academic-projects`): the "Affected Installations" section of
  `Changelog/2.4/Important-PageTemplateNameIsSetExplicitly.rst` explains the
  include order, on `main` and on `2` with the same text.

The documentation is the same for TYPO3 v13 and v14. The include order part
names the static template order of TYPO3 v12, which branch `2` supports.

## Capabilities

### New Capabilities

None. The change sets `skip_specs: true`, it changes no behaviour.

### Modified Capabilities

None.

## Impact

- `Documentation/` of the five extensions above, and one link in the upgrade
  chapter of the other seven manuals.
- Branch `2`: the include order text and the parts of "Before you start" that
  apply to 2.4, as a backport of their own.
- No code, no changelog entry of its own.

## Non-goals

- A central upgrade guide in `academic_base`. ACE-910 chose one upgrade
  chapter per extension.
- A migration from `EXT:educational_course` and its category types
  `course_type` and `begin_course`. Every release from 2.0.0 on ships
  `program_type` and `begin_program`, so it is no 2.x step.
- Deciding whether the synchronisation toggle is shown by default. That is
  ACE-367, this change only documents how an installation hides or locks it.
- Letting the page template name win regardless of the include order.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-10`, with `persons-data-19` folded in), narrowed after ACE-910
delivered the upgrade chapters. Filed as ACE-944.
Relates to ACE-367, ACE-450, ACE-601 and ACE-910.
