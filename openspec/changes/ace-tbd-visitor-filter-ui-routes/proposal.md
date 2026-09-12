## Why

`ace-tbd-visitor-filter-demand-query` lets the persons list accept a function
type or organisational unit filter, but a visitor has no way to choose one,
and the route enhancers only know the page and the letter. One project built
a dropdown with an inline `onchange` handler and a route file of its own. The
handler fails without JavaScript and under a strict Content Security Policy,
and the separate enhancer collided with the shipped one.

## What Changes

- A filter form above the list, shown only when at least one visitor filter
  is enabled. It has one choice per enabled filter, including "all", and a
  submit button, and it contains no inline JavaScript.
- Submitting the form leads to the list URL of the chosen filter, which works
  without JavaScript and is cacheable.
- New `slug` fields on function types and organisational units, generated
  from their names, so filter URLs can be speaking.
- A console command fills the slugs of existing function types and
  organisational units once after the upgrade. No upgrade wizard is added.
- The `ProfileListPlugin` and `ProfileListAndDetailPlugin` enhancers get
  filter routes: each filter alone, both filters, each with a page and each
  with a letter. Every one of them also exists with the view mode segment of
  `ace-tbd-list-view-modes`.
- The documentation tells projects to extend the shipped enhancer key rather
  than adding a second enhancer.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/visitor-filter-form`: how a visitor chooses a list filter
  and which URL the filtered list has.

### Modified Capabilities

None.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`): a new list partial
  and a small non-cacheable filter action of the list and listanddetail
  plugins. It also touches `Configuration/Routes/List.yaml` and
  `Configuration/Routes/ListAndDetail.yaml`, the function type and
  organisational unit TCA (new `slug` columns), one console command, labels,
  the route enhancer and upgrade documentation and the 3.0 changelog.
- Database: two new columns. Existing records get their slug from the
  command, or on their next save; until then their filter URL uses query
  parameters.
- Depends on `ace-tbd-visitor-filter-demand-query` and
  `ace-tbd-list-links-keep-state`. Combines with `ace-tbd-list-view-modes`:
  whichever of the two lands second adds the filter routes with the view
  mode segment.

## Non-goals

- Routes for a letter with a page, with or without a filter. The follow-up
  change that allows
  pagination under an active letter, decided with
  `ace-tbd-list-links-keep-state`, extends this route set.
- An upgrade wizard for the slugs: every wizard is a TYPO3 v15 blocker call
  site (ACE-294), and none is added while v13 is supported.
- Automatic submission on selection.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-13`). One of the six analysed projects carries its own code
for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-visitor-filter-ui-routes` when the issue is filed after
implementation.

Relates to ACE-18 and ACE-623.
