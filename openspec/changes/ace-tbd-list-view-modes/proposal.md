## Why

The list, selected-profiles and selected-contracts plugins of
`academic_persons` (`packages/fgtclb/academic-persons`) offer editors a
"view mode" (tiles or table) and a switch for visitors. No template reads
either, so an editor who chooses "table" gets the tiles. Four projects built
their own table, two added further modes through TSconfig, and one hides the
dead fields with a listener.

## What Changes

- The chosen default view mode is rendered. "list" keeps today's tile grid
  (the stored value stays, to avoid a FlexForm migration; its item is
  relabelled "Tiles"). "table" renders a table with the columns name,
  position, e-mail, phone and room.
- The list and list-and-detail route enhancers get a speaking URL segment
  `/view-mode/{viewMode}` for a mode other than the default, alone, with a
  page and with a letter. A mode that the shipped segment does not know
  keeps a query parameter.
- With the visitor switch enabled, a visitor switches between the allowed
  modes. The active mode is marked, and it is kept across pagination and the
  letter navigation.
- Site settings define the allowed modes (default `list,table`) and the table
  columns.
- An integrator adds a mode with a FlexForm item from page TSconfig, one
  partial named after the mode, and an entry in the allowed modes. No template
  override is needed.
- The card plugin keeps hiding both fields and renders tiles.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/list-view-modes`: how the profile and contract listing
  plugins render and switch between view modes.

### Modified Capabilities

None.

## Impact

- `academic_persons`: the profile controller (resolving the mode), the list,
  selected-profiles and selected-contracts templates, new view mode partials,
  two site settings, labels, and the route enhancers in
  `Configuration/Routes/List.yaml` and
  `Configuration/Routes/ListAndDetail.yaml`.
- Depends on the item and list partials change (`ace-tbd-item-and-list-partials`)
  for the tile grid partial. It also depends on the change for candidate
  `persons-display-10`, "Pagination and letter links keep the active list
  state", for the mode to survive pagination.
- No schema change.

## Non-goals

- A slider or contact card mode (project modes built on the extension point).
- Speaking URLs for the view mode of the selected-profiles and
  selected-contracts plugins, which have no route enhancer.
- Renaming the stored value `list`, and the upgrade wizard it would need
  (every wizard is a TYPO3 v15 blocker call site, ACE-294).
- JavaScript. The switch is a set of links.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-08`). Four of the six analysed projects carry their own code
for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-list-view-modes` when the issue is filed after implementation.
