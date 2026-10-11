## Why

Six extensions register their frontend scripts from their own templates, and
only the study plan offered a switch for its script (ACE-703). An installation
that brings its own script for the markup, or wants none on a site, has to
override every template that registers one, and then either loses the script
or keeps a copy that stops following the original. ACE-891 gives every
extension that loads a script the same switch.

## What Changes

- `academic_jobs` (`packages/fgtclb/academic-jobs/`): the new job form loads
  the rich text editor and the module configuring it unless the integrator
  switches them off.
- `academic_partners` (`packages/fgtclb/academic-partners/`): the partner map
  loads its module and the stylesheets of its map libraries unless switched
  off, on the content element and on a partner page template that renders the
  map. A page template that does not pass the switch keeps loading them.
- `academic_persons` (`packages/fgtclb/academic-persons/`): the public profile
  loads its script unless switched off.
- `academic_persons_edit` (`packages/fgtclb/academic-persons-edit/`): the
  profile editor loads its script unless switched off.
- `academic_programs` (`packages/fgtclb/academic-programs/`): the program list
  and the program finder load their scripts unless switched off.
- Every switch is one site setting and one TypoScript constant of the same
  name, `plugin.tx_<plugin namespace>.assets.js`, on by default. Switching it
  off leaves the markup unchanged.

Nothing is breaking: a site that configures nothing loads every script as
before. The behaviour is the same on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-jobs/frontend-assets`: which scripts the new job form brings to a
  page and how an integrator switches them off.
- `academic-programs/frontend-assets`: which scripts the program list and the
  program finder bring to a page and how an integrator switches them off.

### Modified Capabilities

- `academic-persons/frontend-assets`: the public profile loads its script
  unless the integrator switches it off.
- `academic-persons-edit/frontend-assets`: the editor loads its script unless
  the integrator switches it off.
- `academic-partners/map-libraries`: the map loads its module and the
  stylesheets of its libraries unless the integrator switches them off.

## Impact

Templates and one partial that register a script in the five extensions, their
site set settings definitions, constants and setup, the data processor of the
partner page, a functional test per extension, `docs/architecture/`, the five
manuals and a Feature changelog entry each.

## Non-goals

- A switch per module or per content element.
- Switching the stylesheets of an extension, which ship none since ACE-890.
- The Bootstrap page object of the standalone persons set, which is a page,
  not the script of a plugin.
- Changing the study plan switch, which this change copies.
