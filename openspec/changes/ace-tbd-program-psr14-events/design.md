## Context

See `proposal.md` for the motivation. On main:

- `ProgramController::listAction()` builds the demand through `DemandFactory`,
  queries, collects the applicable categories and assigns, all in one method
  (`Classes/Controller/ProgramController.php`).
- `ProgramDataProcessor` creates `ProgramDataFactory` with `makeInstance()`,
  and the factory maps a fixed field list into `ProgramData`.
- Controllers, factories and models are not `final`, and one project
  subclasses the controller.
- `academic_persons` already has `ModifyProfileDemandEvent` (final,
  getter/setter, dispatched in `ProfileRepository::findByDemand()`) and
  `ModifyListProfilesEvent`.
- Extbase injects the event dispatcher into every `ActionController` through
  `injectEventDispatcher()`, on v13 and on v14 (verified in both installed
  core trees).

## Goals / Non-Goals

**Goals:**

- Seams for the three things projects replace today, without changing any
  constructor a project subclass calls.
- Names that match the persons events and the sibling partners/projects
  change.

**Non-Goals:**

- A generic plugin view event; `ace-tbd-generic-plugin-view-event` proposes
  one in `academic_base`, and these events do not wait for it.

## Decisions

### Three final events in `FGTCLB\AcademicPrograms\Event`

- `ModifyProgramDemandEvent`: the `ProgramDemand` (with `setDemand()`), the
  plugin settings, the content element record, the submitted demand array or
  `null`, and the request. Dispatched in `listAction()` directly after
  `DemandFactory::createDemandObject()`, and in the finder action once
  `ace-tbd-program-finder-element` lands.
- `ModifyListProgramsEvent`: the programs (`iterable`, with a setter), the
  demand, the applicable `CategoryCollection` (with a setter) and a map of
  additional view variables. Dispatched before `assignMultiple()`; the query
  result is assigned unchanged unless a listener replaced it, and the
  additional variables are merged into the assignment without overwriting the
  four existing ones.
- `ModifyProgramDataEvent`: the `ProgramData` (with `setProgram()`) and the
  page record. Dispatched in `ProgramDataProcessor::process()`.

The events are `final` but not `readonly`, because they carry setters; they
hold no service and no state beyond the dispatch.

Rejected: dispatching the demand event in `ProgramRepository::findByDemand()`
as persons does. The repository has no settings, content element or request,
and a list listener needs them to decide.

### The dispatcher comes from Extbase in the controller

The controller uses `$this->eventDispatcher`, which `ActionController`
already provides. Rejected: a new constructor argument, which would break
every project subclass that calls `parent::__construct()` with the current
three arguments.

### Constructor injection in the data processor

`ProgramDataProcessor` receives `ProgramDataFactory` and
`EventDispatcherInterface` through its constructor. It is a DI service
already (tagged `data.processor` in `Configuration/Services.yaml`), so
autowiring resolves both.

Rejected: interfaces with DI aliases for `DemandFactory` and
`ProgramDataFactory`. A listener composes; an alias replaces a whole class for
one tweak. Rejected as well: making the classes `final` first, which breaks
the projects before they have an alternative.

## Risks / Trade-offs

- [A project replaced the processor with a subclass of its own] → Its
  constructor no longer matches; the changelog names the new constructor.
- [Events become API] → They are documented and listed as extension points;
  their getters are stable within 3.x.
- [A listener replaces programs with something that is not a program] → The
  setter is typed `iterable`; the template decides what it renders, as with
  the persons event.

## Open Questions

None.
