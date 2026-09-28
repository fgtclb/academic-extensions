## Context

See `proposal.md` for the motivation. On main:

- `ProgramController::listAction()` builds the demand through `DemandFactory`,
  queries, collects the applicable categories and assigns, all in one method
  (`Classes/Controller/ProgramController.php`). `finderAction()`, added by
  `ace-91-program-finder-element`, does the same for the options of the
  program finder. Both dispatch the generic `ModifyPluginViewEvent` of
  `ace-750-generic-plugin-view-event` after their own assignments, and no
  other event. Since `ace-767-shared-plugin-context` each builds its plugin
  context once, at its start, and hands it to that event.
- `ProgramDataProcessor` creates `ProgramDataFactory` with `makeInstance()`,
  and the factory maps a fixed field list into `ProgramData`. Since
  `ace-733-program-facts-field-list` the processor has a constructor taking
  `ProgramFactsBuilder`, is `public: true` in `Configuration/Services.yaml`
  (so a page object naming it by class name keeps working), and emits
  `facts` next to `program`. This change appends its collaborators to that
  constructor.
- Controllers, factories and models are not `final`, and one project
  subclasses the controller.
- `ace-717-partners-projects-list-events` gave the partner and project lists
  their pair of events, and that shape is merged API: a demand event
  (`ModifyPartnerDemandEvent`: the demand with a setter and the
  `academic_base` plugin context) and a list event (`ModifyPartnerListEvent`:
  the query result and the applicable categories with setters, the demand,
  the view and the context). `docs/architecture/list-plugin-events.md`
  describes the pair and its rules.
- `ace-749-extension-point-policy` made the extension points page of
  `academic_base` a whitelist. An event, and every type it hands over, is
  public API only when it is listed there with `@api`, and
  `ExtensionPointTest` fails when the page and the tags differ.
- `ace-765-filter-match-subcategories` widens each selected category by its
  subcategories inside `ProgramRepository::findByDemand()` when the element
  asks for it, and computes the applicable categories with
  `findAllApplicableWithSubcategories()` then.
- Extbase injects the event dispatcher into every `ActionController` through
  `injectEventDispatcher()`, on v13 and on v14 (verified in both installed
  core trees).

## Goals / Non-Goals

**Goals:**

- Seams for the three things projects replace today, without changing the
  constructor of the program controller, which project subclasses call.
- The same names, arguments and rules as the partner and project events, so
  one page of documentation holds for all three lists.

**Non-Goals:**

- A view event of its own. The generic plugin view event already reaches
  every program plugin.

## Decisions

### Three final events in `FGTCLB\AcademicPrograms\Event`

- `ModifyProgramDemandEvent`: the `ProgramDemand` (with `setDemand()`) and
  the `academic_base` `PluginControllerActionContextInterface`. Dispatched
  in `listAction()` and `finderAction()` directly after
  `DemandFactory::createDemandObject()`, and the demand a listener hands back
  is the one that is queried, and in the list the one assigned as `demand`.
- `ModifyProgramListEvent`: the programs (`QueryResultInterface`, with a
  setter), the applicable `CategoryCollection` (with a setter), the demand,
  the view and the context. Dispatched in both actions after the query and
  the category lookup, before `assignMultiple()`. The categories are not
  recomputed after the event. In the finder, the categories are the options
  of its selects and decide its preselection.
- `ModifyProgramDataEvent`: the `ProgramData` (with `setProgram()`), the
  page record and the request. Dispatched in
  `ProgramDataProcessor::process()` after the factory built the data and
  before the facts are built from it, so the facts show what a listener
  changed.

The events are `final` but not `readonly`, because they carry setters. They
hold what a listener needs for the dispatch and nothing else, the list event
the view among it.

`ModifyProgramListEvent` was planned as `ModifyListProgramsEvent`, with a map
of additional view variables merged into the assignment. The partner and
project events merged since name it after the list and hand over the view
instead, so a listener assigns what it needs, and the program event follows
them. As there, the six variables the action assigns afterwards win over a
listener's variable of the same name, and the generic plugin view event,
which runs last, can replace any of them.

Rejected: dispatching the demand event in `ProgramRepository::findByDemand()`
as persons does. The repository has no settings, content element or request,
and a list listener needs them to decide.

Rejected: dispatching only in the list. The finder offers the categories of
the programs its demand finds, so a listener that narrows the list would
leave the finder offering categories that lead nowhere. The context tells a
listener which of the two plugins is rendering.

### What the events hand over becomes API

`ProgramDemand` and `ProgramData` get `@api` and a row in "What an event
hands over" on the extension points page, as `PartnerDemand` has. The three
events get rows in its event table, and the paragraph on the controllers
that are not final yet names the program events next to the partner and
project ones.

### The subcategory option stays with the repository

The demand event sees the categories as they were selected, not widened: the
widening happens in the repository, from `getIncludeSubcategories()`. A
listener that adds a category therefore gets its subcategories as well when
the element includes them, and a listener that switches the option changes
the matching of the list. A listener that replaces the categories in the list
event builds them the way the controller does, with
`findAllApplicableWithSubcategories()` when the demand includes
subcategories.

### The dispatcher comes from Extbase in the controller

The controller uses `$this->eventDispatcher`, which `ActionController` already
provides, and builds the context with a `protected` method, as the partner
controller does. Each action builds it once, at its start, and hands the same
object to the demand, list and view events (`ace-767-shared-plugin-context`).
Rejected: a new constructor argument, which would break every project subclass
that calls `parent::__construct()` with the current three arguments.

### Constructor injection in the data processor

`ProgramDataProcessor` receives `ProgramDataFactory` and
`EventDispatcherInterface` through its constructor, after
`ProgramFactsBuilder`. It is a public DI service already, so autowiring
resolves both, and a page object that names it by class name still gets it
from the container.

Rejected: interfaces with DI aliases for `DemandFactory` and
`ProgramDataFactory`. A listener composes, while an alias replaces a whole class
for one tweak. Rejected as well: making the classes `final` first, which breaks
the projects before they have an alternative.

## Risks / Trade-offs

- [A project replaced the processor with a subclass of its own] → Its
  constructor no longer matches, and the changelog names the new
  constructor.
- [Events become API] → They are documented and listed as extension points,
  and their getters are stable within 3.x.
- [A demand listener widens as easily as it narrows] → The demand is the
  query, as for partners and projects. The developer chapter says what a
  listener can reach and what it cannot: the page type, the enable fields
  other than `disabled` and the `uid` tiebreaker stay pinned.
- [A listener replaces the programs in the finder] → The finder renders no
  program, so only the categories it hands back change what a visitor sees
  there, and the documentation says so.

## Open Questions

None.
