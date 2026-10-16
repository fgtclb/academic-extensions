## Why

The project lists stop with a PHP error when their sorting setting is a list of
values, which TypoScript makes of `settings.sorting` as soon as it has a child.
The sorting an element stores does not replace such a list, so every project
list the TypoScript applies to fails. ACE-914 guarded the same setting of the
partner and the program lists, not the one of the project lists. All three
lists also stop with a PHP error when a submission or a link carries a list
where the form sends one value, such as `demand[sortingField][]=title`: a
crafted request takes a list element down.

## What Changes

- `academic_projects` (`packages/fgtclb/academic-projects/`): a sorting or an
  active state setting that is a list keeps the default of the extension, title
  A to Z and all projects, instead of a `TypeError` or a PHP warning.
- `academic_partners` (`packages/fgtclb/academic-partners/`),
  `academic_programs` (`packages/fgtclb/academic-programs/`) and
  `academic_projects`: a sorting, a sorting field, a sorting direction or, for
  projects, an active state of a request that is a list is ignored like an
  unknown value, in a link and in a submission alike. The redirect of a
  submission carries the default in its place, or the preset of the element
  where it hides that part of the form.
- No other value of a request is affected: the category filter and the page of
  the partner list already read every shape.

Both supported core versions, TYPO3 v13 and v14, behave the same, before and
after the change.

## Capabilities

### New Capabilities

- `academic-projects/list-defaults`: the sorting and the active state a project
  list shows before the visitor chose one, from the settings of the element.

### Modified Capabilities

- `academic-partners/list-filter-urls`: a sorting value given as a list is
  ignored.
- `academic-programs/list-filter-urls`: a sorting value given as a list is
  ignored.
- `academic-projects/list-filter-urls`: a sorting or active state value given
  as a list is ignored.

## Impact

- The demand factories of the three extensions. No public API, TCA, FlexForm or
  template changes.
- A new `Important` changelog entry per extension in `Changelog/2.4/`, which
  the backport to branch `2` takes over unchanged.
- The integrator manuals of the three list elements and
  `docs/architecture/list-filter-urls.md`.

## Non-goals

- Validating the demand argument as a whole: a demand that is not an array at
  all is left to Extbase, as before.
- Changing which sorting a valid request or setting applies, or the program
  finder fallback to the sorting of the element.
- The other list plugins of the repository (persons, jobs), whose demands
  this issue does not cover.
