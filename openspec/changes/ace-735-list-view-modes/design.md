## Context

The editor-facing fields exist:

- `settings.viewMode.enabled` and `settings.viewMode.default` (`list`,
  `table`, default `list`) are offered in `Configuration/FlexForms/Core13/List.xml:144-175`,
  `Core14/List.xml:144-175`, `SelectedProfiles.xml:42-73` and
  `SelectedContracts.xml:41-72`.
- The card plugin shares `List.xml` and disables both fields
  (`Configuration/TSconfig/Card/page.tsconfig:43-44`).
- Nothing in `Resources/Private` or `Classes` reads them; a grep for
  `viewMode` finds labels and the card TSconfig only.

Site settings are declared in `Configuration/Sets/Full/settings.definitions.yaml`,
repeated with the same default in `Configuration/TypoScript/Default/constants.typoscript`,
and mapped in `setup.typoscript`.

This change builds on `ace-716-item-and-list-partials`: the tile grid becomes
`Profile/ViewMode/List.html` around its `Profile/List/Items`. The links that
keep the mode across pagination and letters come from the change for candidate
`persons-display-10`.

## Goals / Non-Goals

**Goals:**

- A mode is a partial plus an allow-list entry; nothing else.
- A request can never select a partial that is not allowed.

**Non-Goals:**

- Client-side switching without a reload.

## Decisions

### The controller resolves the mode, the template renders a partial

A private method of `ProfileController` resolves the mode:

- It takes `settings.viewMode.default`.
- When `settings.viewMode.enabled` is set, a request argument `viewMode`
  overrides the default, provided the value is in
  `settings.viewMode.allowed`. In the list plugins that argument is the
  demand property `demand/viewMode`, see "The switch".
- A value that is not allowed, or that does not match `^[a-z][a-zA-Z0-9]*$`,
  is ignored.

`listAction()`, `selectedProfilesAction()` and `selectedContractsAction()` then
assign `viewMode` and `viewModePartial`, the mode with an upper case first
letter (`ucfirst()`: the pattern admits no underscore, and
`GeneralUtility::underscoredToUpperCamelCase()` would lowercase `contactCards`
to `Contactcards`), and for the switch
`viewModes` - the allowed modes, empty while the switch is off or fewer than
two modes are allowed - and
`defaultViewMode`. The templates render `Profile/ViewMode/{viewModePartial}`;
the list renders it from `Profile/List/ItemList.html`, once per group of a
grouped list, so a table per letter group under the shipped `groupBy`.

The default mode is the element's `settings.viewMode.default` while it is
allowed; otherwise the tiles, or the first allowed mode where the tiles are not
allowed. An allow-list without a single valid entry is `list`. The list reads
the resolved mode before its events, like `activeListArguments`: a demand a
listener hands back is not resolved again and must not name a partial.

The pattern check keeps a request from ever naming a path segment, even with a
misconfigured allow-list.

Rejected:

- Computing the partial name in Fluid. There is no safe UpperCamelCase for
  arbitrary values there.
- An `f:switch` over known modes. It needs a template override per project
  mode.
- Hiding the dead fields, as one project does. That throws away the feature
  four projects built themselves.

### Shipped modes

- `Profile/ViewMode/List.html`: today's grid, unchanged.
- `Profile/ViewMode/Table.html`: iterates `settings.table.columns` and renders
  `Profile/ViewMode/Table/Cell.html` per column. The cell partial switches over
  the shipped keys `name`, `position`, `organisationalUnit`, `emailAddresses`,
  `phoneNumbers` and `room`. An unknown key renders an empty cell, and a
  project overrides the cell partial to add one. While the element restricts
  its fields (`settings.showFields`), the action drops a shipped contract
  column whose field `contracts.<column>` is not among them, so the table
  never shows a field the tiles of the same element hide; the name and
  columns of a project stay.

Contract values in the table come from the contract selection of
`ace-719-contract-display-policy`, which has landed: a profile row shows the
contracts `persons:contracts` selects for the plugin settings, one value per
line, and a row of the selected contracts element shows its own contract.

### Site settings

- `plugin.tx_academicpersons.viewMode.allowed` (string, default `list,table`).
- `plugin.tx_academicpersons.table.columns` (string, default
  `name,position,emailAddresses,phoneNumbers,room`).

Both are site-wide: which modes a site supports is design, not a per-element
choice.

### Decided: the stored value `list` stays, its item is relabelled

The FlexForm value `list` for the tile grid is kept in all four FlexForms
(`Core13/List.xml`, `Core14/List.xml`, `SelectedProfiles.xml`,
`SelectedContracts.xml`). Only its item label changes, to "Tiles" and
"Kacheln".

A rename to `grid` would need an upgrade wizard rewriting `pi_flexform`, and
every new wizard is a call site of the v15-blocking
`Install\Attribute\UpgradeWizard` API (ACE-294). The label is the only
misleading part.

### Decided: a `/view-mode/{viewMode}` route with a static value mapper

Both shipped enhancers, `ProfileListPlugin` (`Configuration/Routes/List.yaml`)
and `ProfileListAndDetailPlugin` (`ListAndDetail.yaml`), get explicit routes
with the static segment `view-mode` followed by `{viewMode}`:

- the view mode alone;
- the view mode followed by `{localized_page}-{page}`;
- the view mode followed by `{letter}`, without a page variant, because
  pagination is off under a letter.

`{viewMode}` maps through a `StaticValueMapper` whose map holds the shipped
modes `list` and `table`. Every variable of these routes gets explicit
`requirements`, because an aspect makes a path variable greedy - see the
outcome below for what they do and do not separate; `/{profile_name}` stays
apart only because its mapper rejects the value. The routes are explicit
rather than an optional segment, because Symfony omits only trailing defaults
(ACE-623). A link to the default mode carries no view mode, since the active
list state only holds values that differ from their default, so the plain list
URL stays the URL of the default mode. The selected-profiles and
selected-contracts plugins have no route enhancer; their mode stays a
cHash-protected query argument.

A mode that is allowed but missing from the map cannot be generated by the
mapper, so its links fall back to a cHash-protected query argument and still
work. Whether the static mapper is enough is verified in the
implementation, with tests: a project adds a mode by extending the map of
the shipped enhancer key in its site configuration. If that is not possible
without copying the enhancer, a dedicated mapper replaces the static one. It
is site-aware, takes its values from the `viewMode.allowed` site setting and
keeps the pattern check of the mode resolution.

Outcome: the static mapper is enough. `YamlFileLoader::processImports()`
merges a site's own `routeEnhancers` over the imported file with
`ArrayUtility::replaceAndAppendScalarValuesRecursive()` on v13 and v14, so a
site that imports `List.yaml` and adds
`ProfileListPlugin.aspects.viewMode.map.contact-cards: contact` gets
`/view-mode/contact-cards` for its mode, and the shipped entries stay. The
segment is the key of the map and need not be the name of the mode. No
dedicated mapper is shipped.

The `requirements` are `viewMode: '[^/]+'`, `localized_page: '[^/]+'`,
`page: '[0-9]+'` and `letter: '[a-z]'`. They are enhancer-wide - an Extbase
enhancer has no per-route requirements - so they pin the existing page and
letter routes as well, without a change for any URL those mappers resolve.
Measured: with the shipped mappers they are not load-bearing, because every
mapper rejects a value that spans two segments and the matcher falls through;
the routing tests pass without them. They stay as the design asked, so a
site's own mapper cannot turn a greedy variable into a multi-segment match.
The view mode requirement is deliberately looser than the partial-name
pattern, which the controller enforces anyway: a site may map a segment such
as `contact-cards`.

The filter routes of `ace-tbd-visitor-filter-ui-routes` combine with this
segment. Whichever of the two changes lands second adds the combined routes.

Rejected: keeping the mode a query argument. It works, but leaves the list
URL a visitor shares from a table view unreadable. Also rejected: a bare
`/{viewMode}` segment, which competes with `/{letter}` and `/{profile_name}`
for the same path shape.

### The switch

`Profile/ViewMode/Switch.html` renders one link per allowed mode, with
`rel="nofollow"` like the letter links, and `aria-current="true"` on the
active one. A link that carries no argument at all - the default mode of a
list without page or letter, the default mode of a selected element - is the
page itself (`f:uri.page()`): an action link would carry the controller, and
with it a query string and a cHash, where the list has a plain URL. It builds
its arguments from the active list state of `persons-display-10` and changes
only `viewMode`: it overrides it with the chosen mode, and removes it for the
default mode, so a link to the default mode carries none.

That state, `activeListArguments`, holds properties of the profile demand
only: those on the list of visitor-settable properties in `ProfileController`
whose value differs from that of a fresh demand. For the pagination and letter
links to carry the mode, the list and list-and-detail plugins take it as the
demand property `demand/viewMode`, add it to that list, and map that argument
in the `/view-mode/{viewMode}` routes. `adoptSettings()` writes the **resolved**
mode back into the demand, as `ace-tbd-visitor-filter-demand-query` does for
its filters: the default mode as the empty value, so a link to it carries
none, and a mode the resolution rejected as the empty value as well, so it
never reaches a link. The selected-profiles and selected-contracts plugins
have no demand and no navigation links; there the mode stays the plugin
argument `viewMode`.

Guessed layout — a sketch, not a design:

```text
[ Tiles | Table ]                                 <- only with viewMode.enabled
+----------------+-------------+------------+------------+-------+
| Name           | Position    | E-mail     | Phone      | Room  |
+----------------+-------------+------------+------------+-------+
| Dr. A. Achterb.| Professor   | a@ex.test  | +49 ...123 | B 1.02|
| M. Vogel       | Staff       | v@ex.test  | +49 ...456 | C 0.11|
+----------------+-------------+------------+------------+-------+
                    << 1 2 3 >>
```

## Risks / Trade-offs

- Allowing a mode without shipping its partial. → Fluid throws for the missing
  partial. The documentation lists the three steps; the functional test of a
  project mode shows the complete set.
- The stored value `list` names a grid. → Kept; the item label says
  "Tiles".
- Cache variants per mode. → Every mode is a routed or cHash-protected URL,
  one page cache entry each, like the letters.
- More explicit routes per enhancer. → Each gets a routing test for
  generation and resolution; optional segments are not an alternative
  (ACE-623).

## Open Questions

None.
