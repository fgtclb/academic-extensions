## Why

The backport of the `ACE-705` commit of `main` to branch `2`. Specs are branch
scoped, so it is re-derived here rather than moved; `## Source` names where the
fix landed on `main`.

The defect is the same on this branch, in the same file and with the same
lines. The script of the study plan content element of `academic_study_plan`
(`packages/fgtclb/academic-study-plan`) builds the category filter by cloning
the one list item the template renders and substituting the category's id,
colour and title into the placeholders it carries. It does that on the markup
as a **string** and parses the result with `innerHTML`, so a category title
containing `<` reaches the page as markup rather than as text - on the filter
button and in its `aria-label`. A category title is written by an editor in the
backend.

The `f:format.htmlspecialchars()` around `data-categories` does not protect it:
it escapes the attribute, and `element.dataset.categories` hands the script the
decoded JSON back.

## What Changes

- A category title is rendered as text in the filter, whatever it contains.
- A category colour that is not a colour is dropped rather than written into
  the `style` attribute the script sets, where a `;` would start a declaration
  of its own.
- Nothing else. A category whose title and colour are ordinary renders exactly
  as before.

Behaviour is identical on TYPO3 v12 and v13: the file is version independent
and no core API is involved.

## Capabilities

### New Capabilities

- `academic-study-plan/frontend-filter`: what the category filter of the study
  plan renders for the categories the modules carry.

### Modified Capabilities

None.

## Impact

- One TypeScript source and its committed build artifact.
- A `Documentation/Changelog/2.4/Important-*.rst` entry.
- No PHP, TCA, TypoScript or database change.

## Non-goals

- **A test.** This branch has no JavaScript test suite: no `testJs` in
  `Build/Scripts/runTests.sh`, no `test` script in `Build/package.json`, no
  `Build/tests/` and no `Tests/JavaScript/` in any extension. The fix is proven
  on `main`, where the suite exists and a hostile fixture goes red without it,
  and it arrives here unproven. Backporting the harness is an order of
  magnitude more work than this fix and belongs to an issue of its own.
- The partial split and the data attribute contract of ACE-704, which is
  `main` only: this branch has the same class based script and no reason to
  change it.

## Source

Backport of ACE-705, filed for both branches. The fix landed on `main` as the
commit `[BUGFIX] ACE-705: Render category titles as text` of pull request 707.
It is re-derived from the backport analysis of the file level diff between
`main` and `2`, not moved.

The analysis found `Resources/Private/TypeScript/frontend/academic-study-plan.ts`
to differ in exactly two places, neither of which touches this fix: the
constructor is a parameter property here, which this branch permits because it
has no `testJs` suite to load the source, and `buildCategoryFilter()` here does
not remove a `hidden` attribute from the clone, because the `<li hidden>` of the
template is an ACE-703 change that is `main` only. No version switch is needed.
