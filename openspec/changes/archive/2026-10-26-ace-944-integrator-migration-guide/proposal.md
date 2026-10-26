## Why

The upgrade chapters of ACE-910 leave two things on this branch to the
integrator: an installation outside the documented path from 2.3 to 2.4, and
the include order that decides whether the page template name the extensions
set since 2.4 takes effect (ACE-944). This backports the parts of the change
of `main` that apply to 2.4, archived there as
`openspec/changes/archive/2026-10-26-ace-944-integrator-migration-guide`.

## What Changes

- `academic_base` (`packages/fgtclb/academic-base`): "Installations outside
  these paths" in "Before you start" of the upgrade chapter, with the label of
  `main`, for TYPO3 v12, releases older than 2.3, unreleased revisions and
  development constraints of earlier versions. Unlike `main`, it has no step
  to 3.0.
- `academic_programs`, `academic_partners` and `academic_projects`: the
  include order in "Affected Installations" of
  `Changelog/2.4/Important-PageTemplateNameIsSetExplicitly.rst`,
  byte-identical with `main`. It names TYPO3 v12, v13 and v14 and is the same
  file on both branches.

Left out: the section of `academic_persons_edit`, whose 3.0 upgrade pages do
not exist here, and links from the other manuals. Interlinks resolve against
the manual of `main` on every branch, which links the section there.

## Capabilities

### New Capabilities

None. The change sets `skip_specs: true`, it changes no behaviour.

### Modified Capabilities

None.

## Impact

`Documentation/` of four extensions. No code, no changelog entry of its own.
