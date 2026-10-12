## Why

Programs, partners and projects are pages, and their "last updated" date is a
page field an editor may leave empty. It has no automatic value. The sorting
option "Last updated" orders by that field alone, so every page nobody dated by
hand collects in one block at the end of a descending list, and at the start of
an ascending one. On a grown page tree that makes the option close to useless,
for an editor who presets it in a content element as much as for a visitor who
picks it in the sorting select.

## What Changes

- Sorting by "last updated" orders a page without an entered date by the time
  its page record was last saved, and a page with an entered date by that date,
  in both directions.
- Pages that end up equal in that ordering keep the stable order they have
  today.
- The change applies to the program list, the partner list and map, and the
  project lists, wherever the option is offered: the content element, the
  sorting select and the speaking list URLs.
- An `Important` changelog entry in each of the three extensions, because the
  order of existing lists sorted this way changes visibly, page 1 of a
  paginated partner list included.

Which save time counts, and how the ordering reaches the database, are open
decisions for the maintainer, see `design.md`. The specs follow the
recommended option.

The behaviour is the same on TYPO3 v13 and v14, and on every supported
database system.

## Capabilities

### New Capabilities

- `academic-partners/list-sorting`: how the partner list and map order their
  partners for the sorting options they offer.
- `academic-projects/list-sorting`: how the project lists order their projects
  for the sorting options they offer.

### Modified Capabilities

- `academic-programs/program-list-sorting`: adds how "last updated" orders
  programs without an entered date.

## Impact

- `academic_programs` (`packages/fgtclb/academic-programs`), `academic_partners`
  (`packages/fgtclb/academic-partners`), `academic_projects`
  (`packages/fgtclb/academic-projects`): the list query of each, functional
  tests, an `Important` entry in `Documentation/Changelog/3.0/`.
- `academic_base` (`packages/fgtclb/academic-base`): one shared, internal
  ordering mechanism for the three repositories, under the recommended option.
- No new sorting option, no stored value changes. A schema change only under
  the rejected persisted column option.

## Non-goals

- Changing what "last updated" shows anywhere a template prints it, or filling
  the field automatically.
- A setting to switch the fallback off. The option promises the last update,
  and a page without an entered date is not "never updated".
- Other sorting options, including the start date and budget of projects.
- Backporting to branch `2`. It is a behaviour change of a sorting option in
  a maintenance line and would need TYPO3 v12 coverage. Only on request.

## Source

Reported for programs and partners by one project, which carries the
ordering as its own query listener today. `academic_projects` has the same
option and the same query and was added when the issue was re-checked. Tracked
as ACE-624.
