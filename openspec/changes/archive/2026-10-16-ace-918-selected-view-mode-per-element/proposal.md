## Why

The "Selected profiles" and "Selected contracts" elements of `academic_persons`
(`packages/fgtclb/academic-persons`) read the view mode a visitor chose from a
plugin argument that every element of the same type on a page shares. Choosing
the table in one element switches every other element of that type that offers
the switch, so a page cannot show one selection as tiles and another as a table
(ACE-918). A page with several selected elements is their typical use, and the
switch is new in 3.0 and not released yet, so its URLs can still change.

## What Changes

- The view mode argument of both elements becomes a map keyed by the uid of
  the content element in the default language:
  `tx_academicpersons_selectedprofiles[viewMode][<uid>]=<mode>`, and the same
  below `tx_academicpersons_selectedcontracts`. Each element reads only its own
  key.
- A translated element uses the uid of its default-language record, so a link
  keeps working when the page is shown in another language, whether the site
  overlays the translation or selects it in its own language.
- The links of the switch carry only the element's own key. The link to the
  default mode stays the page itself.
- The list and list-and-detail elements keep their plain `viewMode` in the
  demand, their route enhancers and their URLs.
- The manual pages of both elements describe the argument instead of the note
  that two elements share the mode.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/list-view-modes`: a requirement is added that each
  selected profiles or selected contracts element keeps the mode chosen for it,
  also on a translated page. The existing requirements hold unchanged.

## Impact

- `academic_persons` only: the profile controller, the switch partial and the
  two selected templates, a new functional test with its fixture, the adapted
  view mode test, the manuals of both elements, the template and plugin view
  event documentation, and `docs/architecture/list-filter-urls.md`. The seed
  comparison of the two page trees of `packages-dev/dev-site` masks the uid
  that keys the view mode argument.
- TYPO3 v13 and v14 behave the same.
- No database, TCA, FlexForm, route enhancer, cache hash or dependency change.
  No shipped route enhancer and no site configuration of the development
  instances names the argument of the selected elements.
- The URLs of the switch of both elements change. A copy of one of the two
  selected templates made from a 3.0 development state renders a switch that
  no element answers to until it passes the new partial argument on.

## Non-goals

- Scoping the arguments of the list elements to their content element. Two
  lists of one plugin type on a page share page, letter, filters and view
  mode, which is the usual Extbase behaviour.
- Keeping the mode of the other elements in a switch link. A link carries the
  element's own key only.
- A speaking URL for the view mode of the selected elements, which have no
  route enhancer.
- Branch `2`, which has no view modes for these elements.
