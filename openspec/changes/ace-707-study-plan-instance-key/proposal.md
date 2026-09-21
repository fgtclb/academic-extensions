## Why

The script of the study plan content element keeps the instances it has started
in a map keyed by the **value** of `data-study-plan`, which is the uid of the
content element. Two plans of one page that carry the same value collapse onto
one instance, and only the first of them is ever started — no category filter,
no semester accordion, no module dialogs on the second. Two containers that
carry no `data-study-plan` at all key on the empty string and collide the same
way.

A page carries the same record twice through an `Insert records` element or a
shortcut, and a template override is free to leave the attribute out: nothing
else on this branch reads it.

The defect was found by the JavaScript test suite of ACE-706 on its first run —
two of its tests drove a module that had never seen their markup, because all
three fixtures carried `data-study-plan="1"`. The fixtures were given distinct
values so that the suite could be merged; this change fixes the defect itself.

## What Changes

- Every study plan of a page is started, whatever its `data-study-plan` value
  is and whether it has one at all.
- The resize handler reaches every plan for the same reason: it iterates the
  instances.
- Nothing else. A page with one plan behaves exactly as before.

Behaviour is identical on TYPO3 v12 and v13: the module is version independent
and no core API is involved.

## Capabilities

### New Capabilities

- `academic-study-plan/frontend-interaction`: which study plans of a page the
  script drives.

### Modified Capabilities

None.

## Impact

- One TypeScript source and its committed build artifact.
- A `Documentation/Changelog/2.4/Important-*.rst` entry: a page that renders
  the element twice renders something a visitor can use afterwards and could
  not before.
- No PHP, TCA, TypoScript or database change.

## Non-goals

- The `data-study-plan-*` markup contract of ACE-704, which is `main` only.
  This change keeps the class based lookup of this branch as it is.

## Source

`main` keys the instances by the element since ACE-704, where the same
reasoning is recorded in its `design.md`. Re-derived here from the module of
this branch rather than moved: the surrounding lookups differ.
