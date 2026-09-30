## Why

A program page fails with a type error when a `PAGEVIEW` site package assigns
a text as `data`, and reads the wrong array as the page record when it assigns
the records of a query. The program data processor reads the page record from
`data` first, the variable a `FLUIDTEMPLATE` page object assigns, and only then
from `page`. `PAGEVIEW` reserves `page` but not `data`, so the value of the site
package reaches the program and every program page of the site breaks.
The partner and project page processors had the same order and were changed
with ACE-785. The program page is the last page type left.

## What Changes

- `academic_programs` (`packages/fgtclb/academic-programs/`): the program data
  processor reads the page record from the page information object `page`
  first, from `data` only when there is none, and adds no program when the
  value is not a non-empty array. This is the order the partner and project
  processors already use.
- A functional test renders a program page with a `PAGEVIEW` site package that
  assigns a text or the records of a query as `data`, and checks the heading
  and the subtitle of the program.
- The same on TYPO3 v13 and v14. There is no difference between the two
  versions: both reserve `page` for `PAGEVIEW`, and neither reserves `data`.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-programs/program-page-layout`: a program page renders when a
  `PAGEVIEW` site package assigns a variable `data` of its own.

## Impact

- `ProgramDataProcessor` of `academic_programs`, its test fixtures, and the
  program page layout test.
- `docs/architecture/page-type-rendering.md` no longer names the program
  processor as the exception.
- An `Important-*.rst` changelog entry in the programs manual. A `PAGEVIEW`
  site package that deliberately fed its own page record to the program page
  through `data` now gets the record of `page`.

## Non-goals

- The study plan processor reads `data` too, but there `data` is the record of
  the content element, which is what it needs.
- No change on branch `2`. Its program, partner and project processors all
  read `data` first, and TYPO3 v13 offers `PAGEVIEW` there too, so the defect
  exists on that branch in all three. A backport is a change of its own on that
  branch.
