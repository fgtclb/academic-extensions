## Context

`FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState` is a backed enum with
`ALL`, `ACTIVE` and `COMPLETED` and a unit test
(`Tests/Unit/Domain/Model/Dto/ActiveStateTest.php`). The rule lives only in
`ProjectRepository::findByDemand()`
(`packages/fgtclb/academic-projects/Classes/Domain/Repository/ProjectRepository.php:57-80`):
active is `txAcademicprojectsEndDate = 0 OR > new \DateTime()`, completed is
`> 0 AND < new \DateTime()`. `Project::$endDate` is a `?\DateTime` mapped to
`tx_academicprojects_end_date`, an `int(11) DEFAULT NULL` column with TCA type
`datetime` and format `date`.

The labels `activeState.active` and `activeState.completed` exist in
`Resources/Private/Language/locallang.xlf` (`:59`, `:62`). The FlexForm
`Configuration/FlexForms/ProjectSettings.xml` has flat settings, among them
`settings.hideActiveState` and `settings.activeState` for the filter.

`= 0` does not match a `NULL` column, and `NULL` is reachable: the column is
`int(11) DEFAULT NULL` (`ext_tables.sql:6`) without a TCA default and
without `nullable` (`Configuration/TCA/Overrides/pages.php:104-114`).
DataHandler writes `0` when the form saves the empty field, but a page that
never went through the form with that field (created in the page tree,
imported, or switched to the project page type) keeps the column default
`NULL`. Such a page is in neither filter, while the model rule below calls
it active.

## Goals / Non-Goals

**Goals:**

- One rule in PHP, pinned against the query by tests.
- A badge that changes nothing unless switched on.

**Non-Goals:**

- Changing the query of the filter.

## Decisions

### The model computes the state from the end date

`Project::getActiveState(): ActiveState` returns `ACTIVE` or `COMPLETED`,
never `ALL`. The comparison lives in a new named constructor
`ActiveState::fromEndDate(?\DateTimeInterface $endDate, \DateTimeInterface $now)`,
so it is unit tested without a model and the model method stays one line.
Templates read `{project.activeState.value}`.

Rejected: a stored status column maintained by editors. It duplicates the end
date and goes stale. Rejected: a ViewHelper computing the state, which would
put the rule into a third place.

### "Now" is taken the way the repository takes it

The model passes `new \DateTime()`, as the repository does, so the two agree
at the boundary. Rejected: the `date` aspect of the context. It is the better
source, but using it in the model alone would split the two; both can move to
it together later.

### The badge sits in the item partial behind a flat option

`settings.showActiveStateBadge` is a `check` field with `checkboxToggle`, and
the TypoScript setup sets the default `0`, so stored FlexForms without the
key stay without badge. `Partials/Project/Item.html` renders
`<span class="badge academic-projects-item__state academic-projects-item__state--{state}">`
with `f:translate` of `activeState.{state}`.

### Decided: the `NULL` gap of the filter is a separate bugfix change

The "Active" filter is fixed to include an end date of `NULL` in its own
bugfix change, not in this one. This change leaves the query untouched.

Fixing the filter changes a listed result for every site with such pages,
which is a bugfix with a changelog entry of its own; mixed into an additive
change, it would hide which half changed the list. The agreement test of
task 1.3 pins the gap: it asserts that a `NULL` page is in the state active
and, today, missing from the "Active" filter, with a comment naming the
follow-up. The bugfix change flips that one assertion.

## Risks / Trade-offs

- [The model rule and the query can drift] → A unit test pins the rule and a
  functional test pins the agreement with both filters on the same fixtures.
- [Until the bugfix lands, a page with a `NULL` end date shows the badge
  "Active" but is missing from the "Active" filter] → Pinned by task 1.3 and
  named in the manual.
- [A cached list page shows the state of the time it was rendered] → The same
  holds for the filter today; the manual says so.

## Open Questions

None.
