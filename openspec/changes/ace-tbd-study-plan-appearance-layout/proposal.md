## Why

The study plan element of `academic_study_plan`
(`packages/fgtclb/academic-study-plan`) offers editors the Appearance tab with
frame, space before and space after, but none of those choices has any effect,
and the element has no `c<uid>` anchor, so links to it do not jump. Its
template renders the header itself instead of rendering through the Default
layout, which is where these settings are applied. ace-demo and two other
projects fixed it in their template overrides by switching to the layout.

## What Changes

- The study plan element renders through the Default content element layout,
  as other content elements do.
- The editor's frame, space before and space after choices are applied, and
  the element gets its anchor.
- The header is rendered by the layout, once; visibly it moves from inside
  the study plan wrapper to before it.
- The interaction of the study plan is unchanged.

This is a defect fix. Behaviour is identical on TYPO3 v13 and v14, and the
same defect exists on branch `2`.

## Capabilities

### New Capabilities

- `academic-study-plan/content-element-frame`: how the study plan element
  applies the appearance settings shared by all content elements.

### Modified Capabilities

None.

## Impact

- The element template gains a layout and a `Main` section.
- The rendered markup gains the frame wrapper and the anchor; CSS that
  addressed the header inside the study plan wrapper may need adjusting.
- No PHP, TCA or database change.

## Non-goals

- Splitting the template into partials; that is
  `ace-tbd-study-plan-partials-js-contract`.
- A layout of the extension's own.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-16`). Three of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-study-plan-appearance-layout` when the issue is filed after
implementation.
