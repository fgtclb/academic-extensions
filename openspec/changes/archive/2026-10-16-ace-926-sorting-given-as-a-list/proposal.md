## Why

The project lists stop with a PHP error when their sorting setting is a list of
values, which TypoScript makes of `settings.sorting` as soon as it has a child.
The sorting an element stores does not replace such a list, so every project
list the TypoScript applies to fails. ACE-914 guarded the same setting of the
partner and the program lists, not the one of the project lists. All three
lists also stop with a PHP error when a submission or a link carries a list
where the form sends one value, such as `demand[sortingField][]=title`: a
crafted request takes a list element down.

This backports the change `ace-926-sorting-given-as-a-list` of `main`
(`openspec/changes/archive/2026-10-16-ace-926-sorting-given-as-a-list/` there).

## What Changes

- `academic_projects` (`packages/fgtclb/academic-projects/`): a sorting or an
  active state setting that is a list keeps the default of the extension, title
  A to Z and all projects, instead of a `TypeError` or a PHP warning.
- `academic_partners` (`packages/fgtclb/academic-partners/`),
  `academic_programs` (`packages/fgtclb/academic-programs/`) and
  `academic_projects`: a sorting, a sorting field, a sorting direction or, for
  projects, an active state of a request that is a list is ignored like an
  unknown value, in a submission and in a link alike, and the default applies
  in its place, or the preset of the element where it hides that part of the
  form. The partner map is covered with the partner list.
- No other value of a request is affected: the category filter already reads
  every shape.

Both supported core versions, TYPO3 v12 and v13, behave the same, before and
after the change. On this branch the form posts to the list action itself,
there is no redirect of a submission as on `main`.

## Capabilities

### New Capabilities

- `academic-projects/list-defaults`: the sorting and the active state a project
  list shows before the visitor chose one, from the settings of the element.

### Modified Capabilities

- `academic-partners/list-sorting`: a sorting value given as a list is
  ignored.
- `academic-programs/program-list-sorting`: a sorting value given as a list is
  ignored.
- `academic-projects/list-filter`: a sorting or active state value given as a
  list is ignored.

`main` puts the request side into `list-filter-urls` specs, which this branch
does not have, as it has neither the redirect of a submission nor the URLs of
a filtered list that those specs describe.

## Impact

- The demand factories of the three extensions. No public API, TCA, FlexForm or
  template changes.
- A new `Important` changelog entry per extension in `Changelog/2.4/`,
  byte-identical to the one on `main`.
- `docs/architecture/list-filter-types.md`, where this branch documents what
  the demand factories accept, and the unit test count of
  `docs/testing/unit-tests.md`. This branch has no integrator manual pages of
  the three list elements, which `main` updates.

## Non-goals

- Validating the demand argument as a whole: a demand that is not an array at
  all is left to Extbase, as before.
- Changing which sorting a valid request or setting applies.
- The other list plugins of the repository (persons, jobs), whose demands
  this issue does not cover.
