## Context

Verified on this branch by reading and by running the suites:

- `Resources/Private/Templates/Profile/Edit.html` loads CKEditor 4.22.1
  `standard` from a content delivery network and the built configuration module
  `Resources/Public/JavaScript/frontend/ckeditor.js`, generated from
  `Resources/Private/TypeScript/frontend/ckeditor.ts`.
- That configuration declared `language: 'en'` and the toolbar groups
  `basicstyles`, `paragraph/list` and `clipboard/cleanup`. The `standard` build
  ships the link plugin, so only the group was missing.
- The editor attaches to every textarea carrying the class `rich-text`, which
  the form partial sets for the five rich text fields of the profile.
- There is no server side sanitiser between the editor and the database on this
  branch: the submitted value is stored as it arrives.

## Goals / Non-Goals

**Goals:**

- Let the two installations that ship their own editor configuration drop it.
- Keep the stored markup readable.

**Non-Goals:**

- A JavaScript test. This branch has seven node suites and no `testJs`, so the
  configuration object cannot be asserted by an automated test here.

## Decisions

### The toolbar group, not a copied configuration

`toolbarGroups` gains `{ name: 'links', groups: ['links'] }` and `removeButtons`
gains `Anchor`; `linkShowAdvancedTab` is `false`. That is the same shape the
backend preset for profile information already uses
(`EXT:academic_persons/Configuration/CKEditor/LinkOnly.yaml`), so the two agree
rather than each inventing a toolbar.

Rejected: shipping one of the installations' copies. They differ from each other
and from the upstream source, and adopting one would freeze a fork.

### The language comes from the document

The editor language is the primary subtag of `document.documentElement.lang`,
falling back to `en`. CKEditor names its translation bundles by that subtag and
falls back to English itself for one it does not ship, so no list of supported
languages has to be maintained here.

Rejected: passing the language from Fluid into a data attribute. It would add a
template contract for a value the document already carries.

### Writer rules for the block elements

On `instanceReady` the writer rules of `p`, `ul`, `ol`, `li` and `h2` to `h6`
get `breakAfterOpen: false`, `breakBeforeClose: false`, `breakAfterClose: true`
and `indent: false`, so each block element is written on its own line and no
leading whitespace enters the stored value.

### Decided: no server side sanitiser in this bugfix

The link button adds no input capability — a request could already carry any
markup. What decides whether a dangerous URI reaches a visitor is the rendering,
and that was measured rather than assumed: on both core versions the detail view
renders an `https` link and renders neither a `javascript:` nor a `data:` URI,
because the `parseFunc` behind `f:format.html()` removes them. A DataHandler
save of such a value was probed as well and stores it unchanged, so the backend
write path strips nothing either.

A sanitiser for the submitted value, if wanted, is a separate security change.

## Risks / Trade-offs

- [A link carries a protocol the rendering does not accept] → The rendering
  refuses it, pinned by a functional test on v12 and v13. CKEditor 4 offers no
  configuration to restrict the protocol drop-down, so the guarantee is on the
  rendering side, where it belongs.
- [No automated test for the toolbar itself] → The configuration is checked by
  hand in the v12 and v13 development instances, and the result is recorded in
  the pull request. The storing and the rendering of a link are covered by
  functional tests.
- [The backend editor offers no link button for the same fields] → Stated in the
  proposal as a non-goal. Whether CKEditor's client side filtering drops such a
  link when the field is opened and saved in the backend form was not verified:
  it happens in a browser and no functional test can observe it.

## Migration Plan

None. No stored value is rewritten; a field keeps its markup until it is saved
again.

## Open Questions

None.
