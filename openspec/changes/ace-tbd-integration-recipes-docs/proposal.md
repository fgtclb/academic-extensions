## Why

Five projects integrate the academic extensions with the same third-party
tools, and each got it wrong in its own way. Four EXT:solr profile indexes
read `free_field`, a column no version of `academic_persons` has, and build
the detail links by hand. Two projects maintain permission sets by hand. And
there is no documented way to relabel or reorder the academic content elements
in the new content element wizard, which TYPO3 v13 and v14 build from TCA.

## What Changes

- A new `Documentation/Integration/` section in `academic_base`
  (`packages/fgtclb/academic-base`) with three recipes:
  - EXT:solr 13.x: an index queue configuration for the profiles of
    `academic_persons` (`packages/fgtclb/academic-persons`) using columns
    that exist, with the detail link built from the detail plugin's
    arguments; page index queues for program pages (doktype 20,
    `academic_programs`, `packages/fgtclb/academic-programs`) and project
    pages (doktype 30, `academic_projects`,
    `packages/fgtclb/academic-projects`);
  - permission sets for `b13/permission-sets`: an example per extension, as
    documentation only;
  - the new content element wizard: relabelling and positioning the academic
    group, and hiding or ordering academic content elements.
- Links to the section from the persons, programs and projects manuals.
- A functional test pinning the wizard behaviour the recipe documents, on
  TYPO3 v13 and v14. Both versions build the wizard from TCA, so the recipe is
  the same for both; the test proves it.

Nothing changes at runtime.

## Capabilities

### New Capabilities

None. The change is documentation plus a test of existing core behaviour and
sets `skip_specs: true`.

### Modified Capabilities

None.

## Impact

- A documentation section in `academic_base`, links in three more manuals.
- One functional test in `academic_base`.
- No code, configuration or dependency change.

## Non-goals

- Shipping EXT:solr or permission set configuration, as files or as opt-in
  sets; that adds dependencies on third-party release cycles.
- Numbering or relabelling the academic content elements upstream.
- Testing the EXT:solr recipe against a running Solr; the mono repository
  does not install EXT:solr.
- A backport to branch `2`: the recipes are written against 3.0 paths.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-18`). Five of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-integration-recipes-docs` when the issue is filed after
implementation.

Relates to ACE-286.
