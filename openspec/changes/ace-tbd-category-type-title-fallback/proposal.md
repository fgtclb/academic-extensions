## Why

A project may register category types of its own for the groups of
`academic_programs`, `academic_partners` and `academic_projects`, and the
facts, the filters and the finder accept them. In the frontend such a type
has no label: every place looks up `sys_category.<group>.<identifier>` in the
language file of the extension, which only knows the shipped types. The fact
row reads ": Evening classes" and the filter select has no visible label. The
manuals tell an integrator to add the label through `_LOCAL_LANG`, so a type
is declared in one file and labelled in another.

A seventh project, analysed on 2026-10-03, repurposes four shipped university
types with other labels (a teaching form, a group size, a target group, a
funding) rather than declaring its own, and this gap is one of the reasons.
The page module summary of `category_types` already labels a type by its
registered title for the same reason (`ace-687-page-module-category-summary`).

## What Changes

- A category type without a label of its own in the extension's language file
  is labelled with the title its `CategoryTypes.yaml` registers, in the
  language of the page. A title that is an `LLL:` reference is translated, a
  literal title is shown as it is.
- The label of the extension, including one a site sets through `_LOCAL_LANG`
  for the extension or for one plugin, still wins.
- It applies wherever the frontend names a type:
  - `academic_programs` (`packages/fgtclb/academic-programs`): the facts of
    the program page, the program details element and the program card, the
    filter selects of the program list and the selects of the program finder;
  - `academic_partners` (`packages/fgtclb/academic-partners`): the categories
    of the partner page, of the partner card and of the partnership list and
    teaser, and the filter selects of the partner list;
  - `academic_projects` (`packages/fgtclb/academic-projects`): the categories
    of the project page and of the project card, and the filter selects of the
    project list.
- TYPO3 v13 and v14 behave the same.

## Non-goals

- The "all" option of a filter select keeps its own key
  (`sys_category.<group>.allOptions.<identifier>`) and the general fallback.
- No change to the backend: the type select and the page module summary
  already show the registered title.
- No migration of the repurposed shipped types of any project.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-programs/label-overrides`: a category type without a label of the
  extension is labelled with its registered title.
- `academic-partners/label-overrides`: the same for the partner types.
- `academic-projects/label-overrides`: the same for the project types.

## Impact

- Templates and partials of the three extensions that translate
  `sys_category.<group>.<identifier>`, and the program facts.
- A project override of one of those partials keeps the old lookup until it
  adopts the fallback, which the `Feature` changelog entries say.
- Origin: the project differences analysis, seventh project, and
  the documented gap in the facts and filter chapters of the programs manual.
