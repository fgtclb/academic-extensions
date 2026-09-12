## Why

An integrator upgrading from 2.x to 3.0 assembles the steps from about 120
changelog entries in twelve manuals. Only `academic_persons` has an upgrade
page, covering persons and the profile editor. All six projects hit the same
traps: static templates next to site sets, `TsConfig/` imports, hidden
content elements, a page template override that resolves only by include
order (ace-demo, ACE-450, ACE-601), Symfony's `#[AsEventListener]` that
registers nothing (one project), profile editor overrides whose 3.0
replacement is an undocumented setting (ACE-367), and program integrations
with their own finder or category type names.

## What Changes

- A guide "Upgrading from 2.x to 3.0" in the manual of `academic_base`
  (`packages/fgtclb/academic-base`): the ordered cross-extension steps, each
  linking the changelog that owns the detail.
- Chapters in the owning manuals, linked from it:
  - `academic_persons_edit` (`packages/fgtclb/academic-persons-edit`): from
    2.x template overrides to 3.0 configuration, the gaps without a
    replacement, and a checklist per 2.x override path;
  - `academic_programs` (`packages/fgtclb/academic-programs`): page template
    naming and include order, the removed categories partial, unit labels,
    adopting the finder, application link data;
  - `academic_study_plan` (`packages/fgtclb/academic-study-plan`): decimal
    credit points, overrides to partials, asset switches, container-based
    semester plans;
  - `category_types` (`packages/fgtclb/typo3-category-types`): renaming the
    educational_course category types.
- A step for the content-load sets of `academic_partners`,
  `academic_programs` and `academic_projects`, which 3.0 removes as a breaking
  change: what they defined, that the page templates no longer need them, and
  how a site template that still renders `styles.content.getContent` defines
  it itself.
- The existing upgrade page of `academic_persons`
  (`packages/fgtclb/academic-persons`) is linked as the persons chapter.
- Every chapter describing a change that is not released yet says so and
  names the change. The guide is written last, after those changes land.
- Every extension manual links the guide.

The steps are the same on TYPO3 v13 and v14, except the plugin migration,
which has to run on v13 before the core update.

## Capabilities

### New Capabilities

None; the change sets `skip_specs: true` because it changes no behaviour.

### Modified Capabilities

None.

## Impact

- `Documentation/` of the five extensions above, and one link in the index of
  every other manual.
- `docs/workflow/changelog-and-documentation.md` names the guide as the place
  where a new breaking change adds its step.
- No code.

## Non-goals

- An educational_course upgrade wizard: both known projects ran their own,
  and a new wizard adds to the v15 blocker list (ACE-296).
- Recipes for EXT:solr and b13/permission-sets
  (`ace-tbd-integration-recipes-docs`).
- Repeating changelog content.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-10`, with `persons-data-19` and `programs-studyplan-19` folded
in as chapters). All six analysed projects carry their own code for this today.
No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-integrator-migration-guide` when the issue is filed after
implementation.

Implements ACE-367 (the profile editor chapter documents how the
synchronisation toggle is hidden). Relates to ACE-450, ACE-601, ACE-534 and
ACE-296.
