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

This change builds on `ace-702-study-plan-appearance-layout` (the template
renders a `Main` section in the Default layout) and on
`ace-703-study-plan-asset-switch` (the settings definitions file exists).

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
`data-study-plan-semester`, `data-study-plan-semester-header`,
`data-study-plan-module`, `data-study-plan-dialog-trigger` and
`data-study-plan-dialog`. Each lookup tries the attribute first and falls back
to the 3.0 class selector only when the attribute finds nothing, per part, so a
mixed override works.

`data-study-plan-semester` was added while implementing: the list above was
written without it, and the module cannot do without one. Highlighting a
category opens and marks the semester column a matching module sits in, and the
accordion toggles that same column from its header — both walk from a part to
its semester, `closest('.col')` today. Without an attribute for it an override
with its own class names loses the highlighting and the accordion while
everything else keeps working, which is the failure this change exists to end.

The container is the one lookup that is a **union** rather than a fallback:
`[data-study-plan], .academic-study-plan`. Two plans of one page can come from
different templates, and a legacy one must not disappear because another one
carries the attribute. Inside a container the fallback is per part, as above.
The instances are keyed by the container element rather than by the value of
`data-study-plan`, because a legacy container carries no such value and two of
them would otherwise share the key `''` and only the first would start.

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

### Two things review found, and what they changed

The collapsible filter did not collapse. `hidden` only hides an element
because of a *user agent* rule, and `.academic-study-plan .filter` is given
`display: flex` by the extension's own stylesheet — an author rule, which beats
the user agent unconditionally. The dev instances hid it anyway, because
`bk2k/bootstrap-package` ships `[hidden] { display: none !important }`. The
stylesheet now carries `.filter[hidden] { display: none }` next to the rule it
undoes. No suite can catch this: jsdom computes no style and neither PHP suite
loads CSS, so the `hidden` assertion in the jsdom test proves only that the
module sets the attribute. Named as a gap rather than papered over.

The filter was also an injection. Cloning the rendered item and `.replace()`-ing
the category's id, colour and title into its `outerHTML`, then parsing that with
`innerHTML`, makes a category title written in the backend into markup — the
`htmlspecialchars` around `data-categories` protects the attribute only, and
`dataset` hands the value back decoded. Unchanged since 3.0 and present on
branch `2`, so it is a defect of its own: ACE-705, fixed here in a commit of its
own and still to be backported. The substitution walks the clone's attribute
values and text nodes now, and a colour is restricted to the shapes a colour
field can produce because it lands in a `style` attribute.

### Collapsible filter through a setting

A third setting, `plugin.tx_academicstudyplan.filter.collapsible` (`bool`,
default `false`), goes into the settings definitions and constants that
`ace-703-study-plan-asset-switch` adds. The filter partial renders
`data-study-plan-filter-collapsible` when it is on, and the module inserts a
toggle button with `aria-expanded` and `aria-controls`.

The toggle is not inserted at all in two cases, both of which would otherwise
ship a control that is worse than none: a filter with no category in it, and a
container without `data-filter-label`, which is the only label the markup
carries for it. Its `aria-controls` needs an id, and that id comes from a
per-document counter rather than from `data-study-plan` — two plans of one page
can carry the same value, or none.

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

`Tests/JavaScript/academic-study-plan.test.ts` already exists —
`ace-702-study-plan-appearance-layout` created it with the first test of this
module — so it is extended rather than added.

jsdom 29.1.1 declares `HTMLDialogElement` and reflects its `open` property and
implements **none** of `show()`, `showModal()` and `close()`; a module that
opens a dialog dies with a `TypeError`. They are therefore modelled in
`Build/tests/dom.mjs`, like `scrollIntoView` before them: the reflected `open`
attribute, the `close` event, the return value, the focus the opening moves
into the dialog, and `data-test-dialog` for the modality, which nothing else
can observe. Not modelled: the top layer, the backdrop, the escape key and the
focus a browser restores on close — a module that restores it itself has to
keep doing so, which fails loudly rather than passing on a model that did the
work for it.

That model reaches one existing module. `academic-persons-edit` carries an
`isModalCapable()` branch that sets the `open` attribute by hand "a DOM without
it — jsdom's, at the time of writing". Its tests now take the `showModal()`
branch, which is the one a browser takes; the hand-written branch keeps
working and is no longer exercised. It is left as it is: removing it belongs to
that extension, not to this change.

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
