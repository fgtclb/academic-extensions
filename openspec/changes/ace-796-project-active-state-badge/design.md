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
DataHandler writes `0` when a new page is saved through the form with the
field empty, but a page that never went through the form with that field
(created in the page tree, imported, or switched to the project page type)
keeps the column default `NULL`, and saving it later keeps it: on an update
DataHandler compares the submitted empty value with the stored one as
integers, finds `0 === 0` and leaves the column alone. Such a page is in
neither filter, while the model rule below calls it active. That gap is
filed as ACE-433 and pinned by
`ProjectRepositoryTest::aProjectWithoutAnyEndDateValueIsNeitherActiveNorCompleted`.

## Goals / Non-Goals

**Goals:**

- One rule in PHP, pinned against the query by tests.
- A badge that changes nothing unless switched on.

**Non-Goals:**

- Changing the query of the filter.

## Decisions

### The model computes the state from the end date

`Project::getActiveState(): string` returns `active` or `completed`, never
`all`. The comparison lives in a new named constructor
`ActiveState::fromEndDate(?\DateTimeInterface $endDate, \DateTimeInterface $now)`,
which returns the enum, so it is unit tested without a model and the model
method stays one line. Templates read `{project.activeState}`, the same way
they read `{demand.activeState}` of the filter.

The getter returns the string value, not the enum, because Fluid cannot cast
an enum to a string. Rendering the cases below gave the same result on Fluid
4.6.1 (TYPO3 v13.4.35) and Fluid 5.3.2 (TYPO3 v14.3.7): `{state}` in a text,
in an attribute or concatenated into a ViewHelper argument throws
`Cannot cast object ... to string` (1273753083), `{state} == 'active'` is
false, and only `{state.value}`, a comparison with `f:constant` and the enum
passed alone work. A site template
that reads `{project.activeState}` today renders empty and would fail the
whole content element with the enum. The string getter keeps such a template
working, and PHP callers get the enum from `ActiveState::fromEndDate()` or
`ActiveState::from()`.

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
`<span class="badge text-bg-… academic-projects-item__state academic-projects-item__state--{state}">`
above the title, with `f:translate` of `activeState.{state}`. A Bootstrap 5
`.badge` has no background of its own, so the state also picks
`text-bg-success` or `text-bg-secondary`. The modifier class is what a site
styles by.

### Decided: the `NULL` gap of the filter is a separate bugfix change

The "Active" filter is fixed to include an end date of `NULL` in its own
bugfix change, ACE-433, not in this one. This change leaves the query
untouched.

Fixing the filter changes a listed result for every site with such pages,
which is a bugfix with a changelog entry of its own. Mixed into an additive
change, it would hide which half changed the list. Task 1.3 pins the gap in
three places: `aProjectWithoutAnyEndDateValueIsActive` (the state),
`aProjectWithoutAnyEndDateValueIsNeitherActiveNorCompleted` and the "active"
case of `everyProjectAFilterListsIsInTheStateOfThatFilter` (the filter).
ACE-433 turns the two filter assertions around.

## Risks / Trade-offs

- [The model rule and the query can drift] → A unit test pins the rule and a
  functional test pins the agreement with both filters on the same fixtures.
- [Until the bugfix lands, a page with a `NULL` end date shows the badge
  "Active" but is missing from the "Active" filter] → Pinned by task 1.3 and
  named in the manual.
- [A cached list page could show the state of the time it was rendered] → Not
  a risk: both list plugins register `list` as a non-cacheable action
  (`ext_localconf.php`), so the list renders on every request.
- [An end date that is not local midnight] → With
  `extbase.consistentDateTimeHandling`, the default on TYPO3 v14, Extbase sets
  the time of a `format: date` value to midnight, while the query compares the
  stored integer. For a value the backend wrote, which is local midnight,
  nothing differs. An imported timestamp with a time of day can be completed
  for the model and still listed by the "Active" filter for up to a day. The
  docblocks of the rule say so.

## Open Questions

None.
