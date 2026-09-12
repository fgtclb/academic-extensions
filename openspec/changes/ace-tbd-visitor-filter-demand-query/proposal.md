## Why

Visitors cannot narrow the persons list by function type or organisational
unit. An editor can only restrict the list once per content element. One
project forked the profile controller and XCLASSed repositories for a
function type filter. Two more projects ask for the same, and ACE-18 is the
upstream request.

## What Changes

- Two new FlexForm options per list plugin: "Visitors may filter by function
  type" and "Visitors may filter by organisational unit". Both are off by
  default, which keeps today's output.
- With an option on, the list accepts one function type or organisational
  unit value from the request and lists only profiles with a contract that
  carries it.
- Values outside the editor's restriction, unknown values and non-integer
  values are ignored.
- The list view receives the filter options, ordered by name, for the form
  that `ace-tbd-visitor-filter-ui-routes` adds.
- The card plugin, which shares the list FlexForm, hides both options.

Behaviour is identical on TYPO3 v13 and v14. The fields are added to both
`Configuration/FlexForms/Core13/List.xml` and
`Configuration/FlexForms/Core14/List.xml`, because that FlexForm is split per
core version (ACE-560).

## Capabilities

### New Capabilities

- `academic-persons/visitor-list-filter`: which profiles a list shows when a
  visitor filters it by function type or organisational unit.

### Modified Capabilities

None.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`): both list
  FlexForms and their labels, the card page TSconfig, the profile demand and
  controller, the profile repository query, two ordered option queries on the
  function type and organisational unit repositories, the documentation and
  the 3.0 changelog.
- Plugins `list` and `listanddetail`.
- `ModifyProfileDemandEvent` listeners see two new demand properties.
- Depends on `ace-tbd-list-links-keep-state` for pagination and letter links
  that keep the filter.
- No schema change.

## Non-goals

- The filter form and route enhancer entries
  (`ace-tbd-visitor-filter-ui-routes`).
- Multi-value filters and filters by other relations.
- Filters for the card, selected profiles and selected contracts plugins.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-12`). Three of the six analysed projects carry their own code
for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-visitor-filter-demand-query` when the issue is filed after
implementation.

Relates to ACE-18, which this change implements together with
`ace-tbd-visitor-filter-ui-routes`.
