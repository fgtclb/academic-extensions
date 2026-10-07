## Why

The study plan content element of `academic_study_plan`
(`packages/fgtclb/academic-study-plan`) works when two different plans share a
page, but not when an "Insert records" element shows the same plan again.
Both copies render the same dialog ids, and the script looks a dialog up in
the whole document, so a module of the second copy opens the dialog of the
first. When the first copy is hidden in a tab of a theme, the visitor gets an
invisible modal dialog and a page that does not react until Escape. Proven on
TYPO3 13.4.35 and 14.3.7, see `design.md`.

## What Changes

- A module opens the dialog of its own plan, also when the same plan is shown
  more than once and when another dialog of the page carries the same id.
- A plan rendered inside an "Insert records" element gives its dialogs ids
  that carry that element, so the copies no longer repeat a dialog id. A grid
  element that renders its children the same way gives them the prefix as
  well.
- A plan placed on the page renders the same ids as before this change.
- A template override that does not pass the new prefix renders the ids of
  before and still opens its dialogs in the right copy.
- The development seed gets a page with two plans and two "Insert records"
  elements of the first one, in both page trees.

The behaviour is the same on TYPO3 v13 and v14, with one code path.

**Blocked by pull request #850 (ACE-818)**, a task without specs that
rewrites the classes of the same template and partials. This change is built on
its markup: after #850 is merged, or as a pull request stacked on its branch.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-study-plan/frontend-markup-contract`: the dialogs work for a plan
  shown more than once on a page, and a plan inside another content element
  renders dialog ids of its own.

## Impact

- `academic_study_plan`: the data processor, the template and the semester,
  module and dialog partials, the script and its built file, the manual
  chapter "Templates", an `Important` changelog entry.
- `packages-dev/dev-site`: the seed, the legacy scenario generator, both seed
  manifests, both database templates.
- `docs/`: frontend assets, instances, seed verification.
- A plan inside a grid element gets prefixed dialog ids too. Nothing else
  changes for projects.

## Non-goals

- The core's frame id `c<uid>`, which repeats for every copy.
- Unique ids when one "Insert records" element lists the same plan twice, or
  inserts another "Insert records" element that is on the page as well.
  Their dialogs still open in the right copy.
- Other plugins placed several times on one page, analysed on 2026-10-07 and
  parked.
- Branch `2` in this change. The script part is a backport of its own.
