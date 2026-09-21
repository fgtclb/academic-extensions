## Why

The backport of `ace-702-study-plan-appearance-layout` to branch `2`. Specs are
branch scoped, so it is re-derived here rather than moved; `## Source` names the
archive it comes from.

The defect is the same on this branch. The study plan element of
`academic_study_plan` (`packages/fgtclb/academic-study-plan`) offers editors the
Appearance tab with layout, frame, space before, space after, section index and
link to top, and none of those choices has any effect. The element has no `c<uid>`
anchor, so links to it do not jump and a section index menu links to an anchor
that does not exist. Its template renders the header itself instead of rendering
through the Default layout, which is where these settings are applied.

## What Changes

- The study plan element renders through the Default content element layout,
  as other content elements do.
- The editor's layout, frame, space before, space after and link to top
  choices are applied, and the element gets its anchor.
- The header is rendered by the layout, once; visibly it moves from inside
  the study plan wrapper to before it.
- The interaction of the study plan is unchanged.

Behaviour is identical on TYPO3 v12 and v13: the `Layouts/Default.html` of
EXT:fluid_styled_content is byte-identical in v12.4 and v13.4.

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
- `Tests/Functional/ContentElement/` is created. This branch has no rendering
  test for the element at all, so the harness comes with the change rather than
  being extended.
- `docs/architecture/content-element-rendering.md` is added, written for this
  branch rather than copied. The `record` view variable that half of the page
  on `main` is about is a TYPO3 v14 mechanism, so it exists on *neither*
  version this branch supports: the header partial reads `data` directly and
  `lib.contentElement` carries no data processor at all on v12 and v13 alike,
  both files being identical between the two.

## Non-goals

- Splitting the template into partials.
- A layout of the extension's own.
- The node coverage the change carries on `main`. This branch has no `testJs`
  suite, no `Tests/JavaScript/` and no `Build/tsconfig.tests.json`, and its
  `AGENTS.md` does not state the ban on constructor parameter properties. The
  same parameter property is in `academic-study-plan.ts` here, with no gate
  that can observe it; it is left alone deliberately.

## Source

Backport of ACE-702, filed for both branches and relating to ACE-233. It is
re-derived from the backport analysis of the file level diff between `main` and
`2`, not moved: the change is archived on `main` as
`openspec/changes/archive/2026-09-21-ace-702-study-plan-appearance-layout/`.

It comes from the project differences analysis of 2026-09-12, candidate
`programs-studyplan-16`: three of six analysed projects carry a template
override that does nothing but add this layout.
