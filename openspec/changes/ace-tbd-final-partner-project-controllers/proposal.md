## Why

Five plugin controllers are still open on main, and subclassing them is the
only way to extend their plugins today: projects subclass the controller and
re-register the plugin. Every constructor or action change upstream breaks
such a subclass silently or fatally. Once each of these plugins dispatches
events, the subclass has a supported replacement, and leaving the classes
open keeps an unsupported extension point alive that the extension point
policy of `ace-tbd-extension-point-policy` excludes anyway. 3.0.0 is the
major version in which this break is allowed, and four plugin controllers
are `final` already.

## What Changes

- **BREAKING** `academic_partners` (`packages/fgtclb/academic-partners`):
  `PartnerController` becomes `final`. It serves the partner list, the
  partner map and both partnership plugins.
- **BREAKING** `academic_projects` (`packages/fgtclb/academic-projects`):
  `ProjectController` becomes `final`. It serves both project list plugins.
- **BREAKING** `academic_programs` (`packages/fgtclb/academic-programs`):
  `ProgramController` and `DetailsController` become `final`. They serve
  the program list and the program details plugin.
- **BREAKING** `academic_bite_jobs` (`packages/fgtclb/academic-bite-jobs`):
  `BiteJobsController` becomes `final`. It serves the B-ITE job list plugin.
- Their injected collaborators become `private readonly`, as the class
  design rules ask for a class nobody can extend. `DetailsController` drops
  the `DemandFactory` it injects and never reads.
- A project that subclasses or XCLASSes one of these controllers gets a
  fatal error when the class is loaded, and has to move to the events of
  its plugin: `ace-tbd-partners-projects-list-events` for partners and
  projects, `ace-tbd-program-psr14-events` for programs,
  `ace-tbd-bite-jobs-request-result-events` for the B-ITE job list, and
  `ace-tbd-generic-plugin-view-event` for the actions none of those covers.
  Pagination and GET filter URLs of the partner list move to
  `ace-tbd-partner-list-pagination` and `ace-tbd-list-filter-get-urls`.
- An architecture test asserts that every plugin controller is `final`.
- A `Breaking-` changelog entry per extension with a migration example from
  a subclass to an event listener.
- Rendering, templates, plugin registration and settings are unchanged. The
  change is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None. A visitor, an editor and an integrator who configures the plugins
observe no difference; the only observable effect is that a PHP subclass no
longer loads, which is a statement about PHP API, not plugin behaviour. The
extension points that replace the subclass are specified by the event
changes this one depends on. The change sets `skip_specs: true`.

### Modified Capabilities

None.

## Impact

- Five controller classes in four extensions: `academic_partners`
  (`packages/fgtclb/academic-partners`), `academic_projects`
  (`packages/fgtclb/academic-projects`), `academic_programs`
  (`packages/fgtclb/academic-programs`) and `academic_bite_jobs`
  (`packages/fgtclb/academic-bite-jobs`); their constructor property
  visibility, and one constructor argument less in `DetailsController`.
- One architecture test in a `packages-dev/` package.
- Projects with a subclass, an XCLASS or a plugin re-registration pointing
  at a subclass of one of these controllers must migrate in the same
  upgrade.
- `docs/architecture/class-design.md`: the controller counts and a sentence
  on why every plugin controller is closed.
- Depends on `ace-tbd-partners-projects-list-events`,
  `ace-tbd-program-psr14-events` and
  `ace-tbd-bite-jobs-request-result-events`, which have to land first, so
  that every controller made final has an event-based extension point. The
  partnership plugins, the program details plugin and additional view
  variables of the B-ITE job list are covered by none of the three, so
  `ace-tbd-generic-plugin-view-event` has to land first as well.
- Lands after `ace-tbd-partner-list-pagination` and
  `ace-tbd-list-filter-get-urls`, so the `Breaking-` entries point at
  shipped replacements.
- No schema, TCA, template or dependency changes.

## Non-goals

- New events beyond those of the changes this one depends on.
- Other classes the extension point policy names, such as factories,
  repositories or models.
- A deprecation period in 3.x or a deferral to 4.0.
- A backport to branch `2`: it breaks API.

## Source

Follows from the maintainer's decisions on
`ace-tbd-partners-projects-list-events` and on this change (project
differences analysis of 2026-09-12). No YouTrack issue is filed yet; the
change is renamed to `ace-<NNN>-final-partner-project-controllers` when the
issue is filed after implementation.
