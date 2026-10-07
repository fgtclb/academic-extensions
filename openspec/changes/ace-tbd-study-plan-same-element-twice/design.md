## Context

See `proposal.md` for the defect. What the code does on `main` at `f02deaa6d`:

- `Partials/StudyPlan/ModuleDialog.html` renders
  `<dialog id="popup-{module.uid}">`, `Partials/StudyPlan/Module.html` the
  trigger with `data-dialog-id="popup-{module.uid}"`. Module uids cannot repeat
  between two different plans (module, semester and content element are an
  inline chain), so two different plans never collide.
- `academic-study-plan.ts` starts one instance per plan container (ACE-704) and
  scopes everything to it, except the dialog of a trigger:
  `document.getElementById(trigger.dataset.dialogId)` (line 439), with the
  module's own dialog as fallback only when no element of the page has that
  id.
- An "Insert records" element (`CType` `shortcut`) renders the referenced
  record again through the `RECORDS` content object. `{data.uid}` is the uid of
  the referenced record in both copies, so it cannot tell them apart.

### Pull request #850 (ACE-818) comes first

#850 is a task without specs that gives the study plan markup speaking
classes. It replaces the classes of the template and of the filter, semester,
module and dialog partials, three of which this change edits as well, and
renames one class of the script (the filter toggle), away from the dialog
lookup. The semester partial gets a new structure (`role="listitem"` around
`div.ace-semester` with an inner `ul.ace-modules`), the dialog
`class="ace-dialog"`, the trigger `class="ace-trigger"`. The ids and data
attributes are unchanged. Building this change on today's markup would
conflict with it in every partial both edit.

This change is therefore implemented on the markup of #850: on `main` once
#850 is merged, or as a pull request stacked on its branch
`ace-818-speaking-classes` while it is open. The prefix and the script lookup
are added to #850's version of each file, its classes and structure stay as
they are. Nothing of this change's spec depends on #850's classes.

### Evidence

Probed on 2026-10-07 on TYPO3 13.4.35 and 14.3.7 in seeded development
instances:

- Firefox, today's markup and script (screenshots and logs of the prototype
  worktree, `.agent/tmp/studyplan-multi-probe/`): two different plans work. The same plan
  through an "Insert records" element opens the first copy's dialog. With the
  first copy hidden (`display: none`), the visible copy opens a dialog of zero
  size and the page stays inert until Escape. With the script lookup inside
  the plan, every trigger opens its own dialog, also in the hidden case.
- Chromium, a page with plan A, plan B, two "Insert records" elements of A,
  one listing A twice and one inserting the first "Insert records" element:

| Markup           | Script                 | Plans opening their own dialog |
|------------------|------------------------|--------------------------------|
| container prefix | today                  | 5 of 7                         |
| container prefix | lookup inside the plan | 7 of 7, v13 and v14            |

## Goals / Non-Goals

**Goals**: every module opens a dialog of its own copy, and the dialog ids are
unique in every arrangement an editor builds from "Insert records" elements
side by side.

**Non-goals**: the two contrived arrangements of the proposal keep a repeated
id, the core's `c<uid>` frame id stays.

## Decisions

### 1. The script looks a dialog up inside its own plan first

A trigger's `data-dialog-id` is resolved among the `dialog[id]` elements of its
plan container. Only when the plan has no dialog of that id is the document
asked, so an override that renders its dialogs outside the container keeps
working. The module's own dialog stays the fallback for a trigger without
`data-dialog-id`.

This alone fixes the behaviour in every arrangement, including the two
contrived ones, and needs no markup change. It is the part a backport to
branch `2` takes, whose script has the same lookup (line 286).

*Rejected*: dropping `id` and `data-dialog-id` and pairing through the module
only, which the script already supports. It changes the documented markup
contract for overrides, and the core's frame id still repeats.

### 2. A plan inside a content element prefixes its dialog ids with it

Decision 1 makes the dialogs work, but the page still carries every dialog id
once per copy: invalid HTML, and a fragment link, a style or a script of the
site that addresses a dialog by id reaches the first copy only.

`StudyPlanProcessor` asks the request of its content object for the
`currentContentObject` attribute. `AbstractContentObject` sets it to the
renderer that owns the content object, and `RECORDS` and `CONTENT` hand that
request to every record they render, and `f:cObject` and `f:render.record`
the request of their Fluid view. So the processor of a plan sees the renderer
it is rendered from:

| Rendered by                                           | `currentRecord`    | Prefix    |
|-------------------------------------------------------|--------------------|-----------|
| a column through `f:cObject` without a table (theme)  | `''`               | none      |
| a column through `styles.content.get` (from the code) | `pages:<uid>`      | none      |
| an "Insert records" element                           | `tt_content:<uid>` | `c<uid>-` |

Probed on both cores (`/` tree of the seed, rendered through `f:cObject` by the
theme): no prefix for the plans on the page, `c67-`, `c68-` and `c69-` for the
inserted ones, the same as reading the parent record. The `styles.content.get`
row is read from the code, the probed `/legacy/` page held no dialog. A grid
element that renders its children through `RECORDS`, `CONTENT` or a Fluid
record ViewHelper gives them its prefix as well. One that does not hand on the
request gives none, and decision 1 still opens the right dialog.

The processor hands the template `idPrefix`, `c<uid>-` for a `tt_content`
renderer and `''` otherwise. The template passes it to the semester partial,
the semester partial to the module partial, the module partial to the dialog
partial, and both ids become `popup-{idPrefix}{module.uid}`: `popup-25` on
the page, `popup-c67-25` inside element 67.

- The prefix follows the core's `c<uid>` anchor, so it is readable.
- An empty prefix renders the ids of before this change, which keeps a plan
  on the page byte identical and an override that does not pass the variable
  on its ids.
- The uid is that of the "Insert records" record after the overlay. In
  connected mode (`fallbackType` `strict` or `fallback`, as in the seed) a
  translated page carries the same ids as its default language page, in free
  mode the translated uid, unique either way.

`getRequest()` and the attribute are the same on v13 and v14, and `data` and
`currentRecord` are public properties on both, so there is one code path and
no version switch. With a request set, which `RECORDS`, `CONTENT`,
`f:cObject` and `f:render.record` always do, `getRequest()` raises nothing on
v14. Its fallback to `$GLOBALS['TYPO3_REQUEST']` would already have been raised
by the core on the same renderer before the processor runs.

`getRequest()` is marked `@internal` on both cores, and a `@todo` on both
plans to deprecate it. This is accepted: the functional tests render through a real "Insert records" element
on both cores and fail the moment the access breaks, and decision 1 keeps the
dialogs right even when the prefix is lost.

*Rejected*:

- **The parent record** (`$cObj->parentRecord`): public on v13, protected on
  v14 and reachable there only through the `@internal` `getState()`. It needs
  a version switch that phpstan rejects on one core or the other. Probed, it
  gives the same prefixes.
- **`{data.uid}`** as prefix: the same in both copies.
- **A counter per page render** (runtime cache keyed by the record): state in
  a service, and the order of a counter is not stable when a part of the page
  comes from a cache.
- **TypoScript on the "Insert records" element** setting a register around its
  `RECORDS`: it rewrites the rendering of a core content element for every
  site, and themes that render `shortcut` themselves would not get it.
- **`parentRecordNumber`** to separate one element listing the same plan twice:
  deprecated on v14, for a case nobody builds on purpose.

### 3. The seed shows the case

A page `/study-plan/several-plans` below the study plan page, with German and
`/legacy/` copies, holding plan A, plan B (different modules) and two "Insert
records" elements of A. The legacy generator maps the `records` field of a
content element to the legacy uids, otherwise the `/legacy/` copy would show
records of the `/` tree. `LegacyDeliveryTest` masks a uid in an attribute
value with one number today (`popup-25`), and learns a value with two
(`popup-c67-25`). The content uids are the next free ones on `main` at
implementation time.

## Risks / Trade-offs

- [`getRequest()` is deprecated, which its `@todo` plans on both cores] →
  `failOnDeprecation` turns the suites red on the first core raise, and
  decision 1 keeps the dialogs right meanwhile.
- [A theme or grid extension renders content without handing on the request]
  → the plan sees no `tt_content` renderer, renders the ids of before, and
  decision 1 still opens the right dialog.
- [A project override drops `idPrefix`] → the ids of before plus decision 1.
  The changelog names the variable.
- [#850 changes again before it is merged] → the stacked pull request is
  rebased on it, the spec does not depend on its classes.

## Migration Plan

None. A plan on the page renders unchanged. Rollback is a revert.
