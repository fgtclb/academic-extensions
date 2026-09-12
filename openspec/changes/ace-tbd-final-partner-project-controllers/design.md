## Context

Measured on main:

- `academic-partners/Classes/Controller/PartnerController.php` is a plain
  `class` with four `protected` promoted collaborators and the actions
  `list`, `map`, `partnershipsList` and `partnershipsTeaser`.
- `academic-projects/Classes/Controller/ProjectController.php` is a plain
  `class` with three `protected readonly` promoted collaborators and one
  `list` action, registered for both project list plugins.
- `academic-programs/Classes/Controller/ProgramController.php` is a plain
  `class` with three `protected` promoted collaborators and one `list`
  action (plugin `ProgramList`).
- `academic-programs/Classes/Controller/DetailsController.php` is a plain
  `class` with two `protected` promoted collaborators and one `show` action
  (plugin `ProgramDetails`), which assigns the content element record and
  the program of the page it sits on. It injects `DemandFactory` and never
  reads it.
- `academic-bite-jobs/Classes/Controller/BiteJobsController.php` is a plain
  `class` with one `protected readonly` promoted collaborator,
  `BiteJobsService`, and one `list` action (plugin `List`).
- These five are all the plain classes in `Classes/Controller/`. The four
  others are `final` already: the two `ProfileController`s of
  `academic_persons` and `academic_persons_edit`, `JobController` and
  `ContactsController`. They run and are functionally tested on TYPO3 v13
  and v14 alike, so the container and Extbase dispatch accept a final
  controller on both versions.
- No class in `packages/` or `packages-dev/` extends one of the five. None
  of them implements an interface, has a DI alias or is documented as a
  subclassing point; the manuals of `academic_programs` and
  `academic_bite_jobs` name `ProgramController` once, in the route enhancer
  configuration, by class name. A project reaches them only through a
  `configurePlugin()` call or an XCLASS pointing at its own subclass.
- Functional plugin tests exist for every affected extension, among them
  `academic-programs/Tests/Functional/Plugins/AcademicProgramsPluginTest.php`
  (list and details) and
  `academic-bite-jobs/Tests/Functional/Plugins/AcademicBiteJobsListPluginTest.php`.
- Of the analysed projects, the ACE demo subclasses `PartnerController` to
  add pagination, to turn the filter POST into bookmarkable GET URLs and to
  remap a visitor's sorting choice onto another field, and points the
  plugin registration at its subclass. One project subclasses
  `ProgramController` to add a finder action next to the list. None
  subclasses `DetailsController` or `BiteJobsController`; one project runs
  a fork of `academic_bite_jobs` instead.

## Goals / Non-Goals

**Goals:**

- Close the five open plugin controllers in 3.0.0, with a migration each
  project can follow from the changelog alone.
- One rule for every plugin controller, enforced by a test.

**Non-Goals:**

- Changing an action, a view variable or the plugin registration.

## Decisions

### Decided: all five controllers become final

Decided by the maintainer. `PartnerController` and `ProjectController` were
the first two; `ProgramController`, `DetailsController` and
`BiteJobsController` follow in the same change. The reason is one rule for
every plugin controller once it has events: a plugin controller is `final`,
and its plugin is extended through events. Closing only the partner and
project controllers would leave three plugins where subclassing stays the
de facto extension point, and would need a second breaking change in 4.0
for classes that have no other reason to change then.

Rejected: leaving the program and B-ITE controllers for a change of their
own. Their events are proposed for 3.0.0 as well, so the precondition that
made closing them premature, a plugin without any event, is gone by the
time this change lands. `ace-tbd-program-psr14-events` still lists making
the program controller final as a non-goal for a later major version, and
`ace-tbd-bite-jobs-list-grouping-and-views` describes `BiteJobsController`
as not final; this change supersedes both statements for the controllers.

### `final` directly in 3.0.0, as a breaking change

Decided by the maintainer on `ace-tbd-partners-projects-list-events`.
Rejected: keeping the classes open. It leaves subclassing as the de facto
extension point next to the events, and every later constructor change in
3.x becomes a breaking change for projects that were never promised it.
Rejected: deferring to 4.0 with a `@final` docblock in 3.x. PHP cannot warn
about a subclass of a class tagged that way, so the tag reaches nobody who
does not read the source, and the break lands in a major version that has no
other reason to touch these classes.

### Collaborators become `private readonly`

With no subclass possible, `protected` only widens what a reader has to
consider. `private readonly` is the rule of
`docs/architecture/class-design.md` for injected collaborators and matches
the four final controllers. It is part of the same break and adds nothing
observable to it. `readonly class` stays out of reach, because
`ActionController` is not readonly.

`DetailsController` drops its unread `DemandFactory` in the same step: as a
private property it would be reported by PHPStan as written but never read,
and with no subclass left nothing can rely on the argument.

### One architecture test for every plugin controller

A unit test in a `packages-dev/` package covered by the phpunit glob scans
`packages/fgtclb/*/Classes/Controller/`, reflects every non-abstract class
extending Extbase's `ActionController`, and asserts that it is `final`. It
also asserts that it found the nine controllers of main, so a changed scan
path cannot turn it into a test of nothing. It is shown red on main, naming
exactly the five open controllers, before the keyword is added. It sits in
`packages-dev/` for the reason `ace-tbd-extension-point-policy` gives for
its event test: it spans all extensions and must not travel into a split
repository. When that change has landed, both tests live side by side.

The existing functional plugin tests of the four extensions are the
regression check that the container still builds the controllers.

Rejected: one reflection test per controller below each extension's
`Tests/Unit/Controller/`. Five tests for one rule, and a controller added
later would be open until someone remembers to write a sixth. Rejected:
widening the event test of `ace-tbd-extension-point-policy`. That test is
about events and may land in a different order.

### Migration from a subclass, per controller

The `Breaking-` entries map each subclass purpose to its replacement.

`PartnerController` and `ProjectController`, through the events of
`ace-tbd-partners-projects-list-events`:

- Changing the filter or sorting before the query, including remapping a
  sorting choice onto another field: a listener of the demand event, which
  replaces or adjusts the demand.
- Additional view variables, or a replaced or reduced result: a listener of
  the list event.
- The partnership list and teaser actions, which that change gives no
  event: the plugin view event of `ace-tbd-generic-plugin-view-event`.
- Pagination of the partner list: the FlexForm option of
  `ace-tbd-partner-list-pagination`.
- Bookmarkable filter URLs: the redirect of `ace-tbd-list-filter-get-urls`.

`ProgramController`, through the events of `ace-tbd-program-psr14-events`:

- Changing the selection before the query: a listener of
  `ModifyProgramDemandEvent`.
- Replacing or reordering the programs, adjusting the offered filter
  categories, or adding view variables: a listener of
  `ModifyListProgramsEvent`.
- An own finder action next to the list: the finder element of
  `ace-tbd-program-finder-element` where it has shipped; until then a
  project keeps its own plugin with its own controller class, which is
  unaffected by this change.

`DetailsController`:

- Changing the program or adding view variables of the details plugin: a
  listener of the plugin view event of `ace-tbd-generic-plugin-view-event`.
  `ModifyProgramDataEvent` of `ace-tbd-program-psr14-events` changes the
  data of the program page template, which is rendered by a data
  processor, not by this controller.

`BiteJobsController`, through the events of
`ace-tbd-bite-jobs-request-result-events`:

- Changing the request to B-ITE (filter, channel, locale, sort, paging): a
  listener of `ModifyBiteJobsRequestEvent`.
- Removing, enriching or grouping the postings: a listener of
  `AfterBiteJobsFetchedEvent`.
- Additional view variables: a listener of the plugin view event of
  `ace-tbd-generic-plugin-view-event`.

For every controller, the plugin registration: remove the project's own
`configurePlugin()` call or XCLASS registration that points at the
subclass, so the shipped controller serves the plugin again.

The example in each entry shows a subclass overriding the list action (the
`show` action for `DetailsController`) next to a listener class carrying
TYPO3's `#[AsEventListener]`, which does the same through the event.

### Dependencies

Hard dependencies, which have to be merged first, because closing a class
without an event removes the only extension point:

- `ace-tbd-partners-projects-list-events` for `PartnerController` and
  `ProjectController`;
- `ace-tbd-program-psr14-events` for `ProgramController`;
- `ace-tbd-bite-jobs-request-result-events` for `BiteJobsController`;
- `ace-tbd-generic-plugin-view-event` for `DetailsController`, the two
  partnership actions of `PartnerController` and additional view variables
  of `BiteJobsController`. None of the three changes above dispatches an
  event there, so without it the rule of every finalised controller having
  an event-based extension point does not hold. The alternative is a
  details event in `ace-tbd-program-psr14-events`; the precondition task
  accepts either.

Because `ace-tbd-generic-plugin-view-event` lands after
`ace-tbd-extension-point-policy`, this change does too, and its
documentation can name the policy page.

The pagination and filter URL changes, and the program finder element, are
an ordering constraint, not a technical one; they land first so the
changelog can point at shipped features.

### Identical on TYPO3 v13 and v14

`final` and property visibility are PHP, and Extbase instantiates controllers
the same way on both versions. There is no core version switch.

## Risks / Trade-offs

- [A project upgrades without reading the changelog] → the fatal error names
  the class it cannot extend, which leads straight to the `Breaking-` entry.
- [A subclass does something no event covers] → the events expose the
  demand, the result, the categories, the B-ITE request and postings, and
  the view, which covers every purpose of the analysed subclasses; anything
  else is a request for a new event, not a reason to keep the class open.
- [A program finder subclass predates the upstream finder element] → it is
  a subclass of `ProgramController` and stops loading; the entry shows the
  project moving its finder action into a controller of its own until the
  upstream element ships, which is why that element is an ordering
  constraint.
- [A dependency slips out of 3.0.0] → the controller it would cover cannot
  be closed without removing its only extension point; the change then
  waits, or the controller is dropped from it and closed in 4.0.

## Migration Plan

- Remove the subclass and its plugin or XCLASS registration, move each
  override to the replacement named above, and flush the caches.
- Rollback: none needed upstream; a project that cannot migrate stays on
  2.x until it can.

## Open Questions

None.
