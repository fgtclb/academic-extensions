## Why

The partner and project list plugins offer no extension point. A project that
wants to adjust the filter before the query, add a view variable or replace
the result has to subclass the controller and re-register the plugin, which
breaks on every upstream change of the constructor or the action.
`academic_persons` solved the same need with events.

## What Changes

- `academic_partners` (`packages/fgtclb/academic-partners`) dispatches two
  events in its list and map plugins:
  - a demand event after the demand is built from settings and request, in
    which a listener may replace the demand;
  - a list event after the query, in which a listener may replace the
    partners and the categories and assign view variables.
- `academic_projects` (`packages/fgtclb/academic-projects`) dispatches the
  same pair in both project list plugins.
- Both events carry the plugin context of `academic_base`, from which a
  listener reads the request, the content element, the settings and the
  action name.
- The map keeps drawing only partners with coordinates, whatever a demand
  listener does.
- No constructor, signature or template change; behaviour without listeners
  is unchanged. Identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/list-extension-events`: extension points of the partner
  list and map.
- `academic-projects/list-extension-events`: extension points of the project
  lists.

### Modified Capabilities

None.

## Impact

- Four new event classes, dispatch in three actions.
- Projects that subclass `PartnerController` or `ProjectController` for these
  purposes can move to listeners.
- No schema or dependency changes (`academic_base` is already required).

## Non-goals

- Events for the partnership plugins, programs or jobs.
- OR semantics within one category type (one project); it stays project code
  behind the demand event.
- Making the controllers `final`; that is the separate breaking change
  `ace-tbd-final-partner-project-controllers` for 3.0.0, which builds on this
  one.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-15`). Three of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-partners-projects-list-events` when the issue is filed after
implementation.
