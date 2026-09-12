## Why

Projects copy, subclass or XCLASS plugin controllers to add a single view
variable: pagination variables in one project, a replaced detail profile in
another, the detail page id in a third. On main only the new job form of
`academic_jobs` has a view event, and `academic_persons` hands the view to its
events in four of its five actions. Every other plugin action offers no way to
add a variable.

## What Changes

- `academic_base` (`packages/fgtclb/academic-base`) ships a `final`
  `ModifyPluginViewEvent` carrying the plugin action context and the view.
- It is dispatched once per rendering by every Extbase plugin action that
  renders a view:
  - `academic_persons` (`packages/fgtclb/academic-persons`): list, card,
    detail, selected profiles, selected contracts;
  - `academic_jobs` (`packages/fgtclb/academic-jobs`): list, detail, new job
    form;
  - `academic_partners` (`packages/fgtclb/academic-partners`): list, map,
    partnerships list, partnerships teaser;
  - `academic_programs` (`packages/fgtclb/academic-programs`): program list,
    program details;
  - `academic_projects` (`packages/fgtclb/academic-projects`): project list;
  - `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`):
    contact list;
  - `academic_bite_jobs` (`packages/fgtclb/academic-bite-jobs`): job list.
- **BREAKING** The view events the new event replaces are removed in 3.0:
  - `academic_jobs`: `ModifyJobControllerNewActionViewEvent`;
  - `academic_persons`: `ModifyListProfilesEvent`, `ModifyDetailProfileEvent`,
    `ModifySelectedProfilesEvent` and `ModifySelectedContractsEvent`.

  They are no longer dispatched, so their listeners are no longer called. A
  leftover listener does not cause an error, because TYPO3 registers the
  event of a listener as a class name string and PHP checks the parameter
  type only when the method is called; static analysis reports the unknown
  class. A `Breaking-` changelog entry per extension, in the style of a TYPO3
  core hook-to-event transition, shows the migration to the new event and
  links its `Feature-` entry.
- The data the removed persons events let a listener change moves elsewhere:
  the profile demand is changed through the existing demand event before the
  query, the detail page title format through the plugin setting, and view
  variables through the new event.
- Variables an action assigns deliberately after the event, such as the
  validations of the new job form, stay out of a listener's reach.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-base/plugin-view-event`: which plugins let a listener add view
  variables, what the listener receives, and in which order it runs.

### Modified Capabilities

None.

## Impact

- One event class, marked `@api`, and one controller trait in
  `academic_base`; one dispatch per rendering path in nine controllers of
  seven extensions.
- Five event classes removed: one in `academic_jobs`, four in
  `academic_persons`, together with their dispatches and their unit tests.
- Lands after `ace-tbd-single-action-context-interface` and
  `ace-tbd-extension-point-policy`.
- No change to templates, variables, settings or TypoScript.
- A fixture extension for the functional tests.
- Changelog entries in `academic_base` and in the dispatching extensions;
  `Breaking-` entries in `academic_jobs` and `academic_persons`.

## Non-goals

- `academic_study_plan` (`packages/fgtclb/academic-study-plan`): its content
  element renders through a data processor, not through an Extbase action,
  and TypoScript `dataProcessing` already takes additional processors.
- `academic_persons_edit` (`packages/fgtclb/academic-persons-edit`): its
  profile editing controller is being split, and the forms need data-level
  events rather than a view hook.
- Data-level events (demand, query, save); they belong to the families.
- A deprecation phase for the removed events; 3.0.0 is unreleased, and they
  are removed directly.
- The other persons events: the demand event, the profile title placeholder
  event and the profile update events stay.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-12`). Four of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-generic-plugin-view-event` when the issue is filed after
implementation.
