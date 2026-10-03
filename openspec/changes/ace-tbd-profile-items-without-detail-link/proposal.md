## Why

A site that has no profile detail pages still gets a link on every name in a
persons list, a card element and the selected profiles and contracts elements
of `academic_persons` (`packages/fgtclb/academic-persons`). Without a detail
page configured, the link points to the current page with the arguments of the
detail plugin, which shows the same list again. A site can only avoid it by
overriding the item partials.

A seventh project of the project differences analysis (2026-10-03) has no
detail pages by design, an A-Z register and contact cards only, and copies the
whole item and header partials to drop the link. With the item split of
`ace-716-item-and-list-partials` the override shrinks to one partial, but the
dead link stays the default of every site in that position.

## What Changes

- A new site setting and TypoScript constant
  `plugin.tx_academicpersons.detailLink` with the values `link` (the default)
  and `none`. With `none`, the names of profile items are rendered as text,
  without a link, in the list, card, selected profiles and selected contracts
  elements.
- The same choice per content element in the plugin options of those four
  elements, empty by default. An empty choice uses the site setting, a chosen
  value wins over it in both directions.
- The list-and-detail element always links its items, because it shows the
  detail view itself, and does not offer the choice.
- A detail page passed explicitly to the item partial by a template still
  links, whatever the settings say.
- TYPO3 v13 and v14 behave the same.

## Non-goals

- Changing what happens with `link` and no detail page configured: the link
  keeps pointing to the current page, because a site may place the detail
  plugin on the page of its list.
- The page contacts of `academic_contacts4pages`, which render the same item
  with their own settings and keep linking.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/profile-template-partials`: items of the list, card and
  selection elements can be rendered without a detail link, site wide or per
  element.

## Impact

- `Partials/Profile/Item/DetailLink.html`, the TypoScript constants and setup,
  the site settings definition of the persons set, the FlexForms of the list
  (both core variants) and of the two selection elements, backend labels.
- A project override of the detail link or of the whole item ignores the
  settings until it adopts them, which the `Feature` changelog entry says.
- Origin: the project differences analysis, seventh project.
