## Context

See `proposal.md` for the motivation. On main:

- `Resources/Private/Frontend/Default/Templates/AcademicStudyPlan.html:9-130`
  holds all markup.
- The container already carries `data-study-plan="{data.uid}"`, while the
  module initialises on `.academic-study-plan` (`academic-study-plan.ts:302`).
- The module selects `.module`/`.header` (`:59-60`), `.filter` and
  `.filter li` (`:76-77`, the filter optional since `:79`),
  `.module[data-categories]` (`:91`), `.filter button` (`:116`),
  `.modal-trigger` (`:236`) and `.module dialog` (`:255`).
- `partialRootPaths.100` already points at `Frontend/Default/Partials/`
  through a constant, so partials need no TypoScript change.

This change builds on `ace-tbd-study-plan-appearance-layout` (the template
renders a `Main` section in the Default layout) and on
`ace-tbd-study-plan-asset-switch` (the settings definitions file exists).

## Goals / Non-Goals

**Goals:**

- The markup contract is data attributes; classes are styling only.
- A 3.0 override keeps working for the whole 3.x line.

**Non-Goals:**

- Changing the visible default markup or its CSS.

## Decisions

### Four partials

`StudyPlan/Filter`, `StudyPlan/Semester`, `StudyPlan/Module` and
`StudyPlan/ModuleDialog` below `Frontend/Default/Partials/`, each receiving
the variables it renders explicitly rather than `{_all}`, so an override sees
a stable argument list that the documentation names.

### Data attributes with a class fallback

The attributes are `data-study-plan` (container, existing),
`data-study-plan-filter`, `data-study-plan-filter-template`,
`data-study-plan-semester-header`, `data-study-plan-module`,
`data-study-plan-dialog-trigger` and `data-study-plan-dialog`. Each lookup
tries the attribute first and falls back to the 3.0 class selector only when
the attribute finds nothing, per part, so a mixed override works.

The fallback logs nothing: a console message would reach visitors, not
integrators. The deprecation is announced in
`Deprecation-StudyPlanClassSelectors.rst` and the fallback is removed in 4.0.

Rejected: selectors configured as JSON from TypoScript. It moves the contract
into configuration and still breaks on every markup change.

### Decided: the whole module as trigger is an override, not an option

Upstream offers no setting that turns the whole module into the dialog
trigger. The data attribute contract already allows it: a `StudyPlan/Module`
override puts `data-study-plan-dialog-trigger` on the module element itself.
The module therefore looks for the trigger on the module element as well as
inside it, and pairs it with that module's dialog; the jsdom test pins this.
Making the whole module a control nests its content inside interactive
content, which is the accessibility concern, so upstream does not offer it
as a default path.

### Collapsible filter through a setting

A third setting, `plugin.tx_academicstudyplan.filter.collapsible` (`bool`,
default `false`), goes into the settings definitions and constants that
`ace-tbd-study-plan-asset-switch` adds. The filter partial renders
`data-study-plan-filter-collapsible` when it is on, and the module inserts a
toggle button with `aria-expanded` and `aria-controls`.

Rejected: the accordion built in the overrides of ace-demo and another project
as markup of their own; with a setting the behaviour stays in the tested
module.

Guessed layout — a sketch, not a design:

```text
GUESSED  collapsible filter (mobile)
[ Filter modules  v ]
  ( ) Core   ( ) Elective   ( ) Practice
| Sem 1  30 ECTS  [+] | Sem 2  30 ECTS  [+] | ...
| Module A   5  (i)  | Module C  7.5 (i)  |
```

### Testability of the module

The module exports its initialiser so the jsdom tests can run it on a
fixture; the start on `DOMContentLoaded` stays. The source keeps free of
`enum`, `namespace`, parameter properties and decorators.

### Decided: no upstream epic for multi-semester modules

Multi-semester modules and dual study phases are not an upstream epic now.
One project asks for them, and it still runs a fork of the 1.0 predecessor
under the same package name. Moving that project onto upstream through the
partial and data attribute contract of this change comes first; only then is
its data model comparable. The question is reopened when a second project
asks.

## Risks / Trade-offs

- [An override keeps an attribute on the wrong element] → The documentation
  lists each attribute with the element it belongs on, and the legacy fixture
  test guards the fallback.
- [CSS targeting the old nesting] → The visible markup keeps its classes and
  nesting; only attributes are added.

## Migration Plan

Nothing is required on update. An integrator with a 3.0 override adds the
data attributes before 4.0, or replaces the override with a partial override.

## Open Questions

None.
