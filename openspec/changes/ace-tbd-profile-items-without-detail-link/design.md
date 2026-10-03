## Context

See `proposal.md` for the motivation. On `main`:

- `Partials/Profile/Item.html` renders `Profile/Item/DetailLink` and passes
  its text as `link` to `Profile/Header` or `Profile/SectionHeader`, which wrap
  the name in `f:link.typolink`. With an empty `parameter`, `f:link.typolink`
  returns its content without an anchor (read in the v14 source; task 1.1
  confirms it on both core versions), so an empty detail link already means
  "no link".
- `Profile/Item/DetailLink` builds the address from a passed `detailPid`, or
  for the list-and-detail element from the current page, or from
  `settings.detailPid`. A `detailPid` of `0` (the default of the site setting
  and the constant) makes `f:uri.action` link the current page.
- Site settings and constants are kept in step: every path of
  `Configuration/Sets/Full/settings.definitions.yaml` is also assigned in
  `Configuration/TypoScript/Default/constants.typoscript` with the same
  default, and `setup.typoscript` maps it into `settings`
  (`renderContentElementHeader` is the model).
- The list, list-and-detail and card elements share
  `Configuration/FlexForms/Core13|Core14/List.xml`, the selection elements use
  `SelectedProfiles.xml` and `SelectedContracts.xml`.
- `ignoreFlexFormSettingsIfEmpty` of Extbase drops a FlexForm value that is
  `''` **or** `'0'` and keeps the TypoScript value then
  (`FrontendConfigurationManager::removeIgnoredFlexFormSettingsIfEmpty()`, the
  same on v13 and v14). `detailPid`, `pageTitleFormat` and `showFields` use it.
- `academic_contacts4pages` renders the same item from its own plugin, whose
  settings do not carry the persons block.

## Goals / Non-Goals

**Goals:**

- No dead link for a site without detail pages, configured once, and a
  choice per element in both directions.
- Every existing site and every existing element renders exactly as before.

**Non-Goals:**

- See the proposal.

## Decisions

### One string setting with the values `link` and `none`

`detailLink` with `link` and `none`, not a boolean. A boolean element switch
cannot say "link" against a site wide "no link": its `0` is dropped by
`ignoreFlexFormSettingsIfEmpty` exactly like the empty value, so the site
setting would win. Two strings and an empty value give three states.

Only `none` switches the link off. A caller whose settings do not carry the
key, the page contacts and project templates among them, links as before.

Rejected: a boolean site setting plus a separate element field with its own
precedence logic in the template. It moves the merge of the two values into
Fluid, which Extbase already does for `detailPid`.

### The element choice falls back through `ignoreFlexFormSettingsIfEmpty`

`settings.detailLink` is a select in the three FlexForms with the items
"Use the site setting" (`''`, the default), "Link to the detail view"
(`link`) and "No link" (`none`), and `detailLink` is added to
`ignoreFlexFormSettingsIfEmpty` next to `detailPid`. An element with an empty
choice therefore gets the TypoScript value, which the site setting or the
constant fills. Elements stored before the change have no value and behave
as empty.

The site setting uses `type: string` with an `enum` of the two values (site
settings support `enum` since TYPO3 13.3, below the floor of 13.4.35), so the
settings editor offers a select.

### Hidden for the list-and-detail element

The field in `List.xml` carries a `displayCond` on the CType of the record
(`FIELD:parentRec.CType:!=:academicpersons_listanddetail`), because that
element links whatever is chosen. If the condition does not resolve on one of
the core versions, the field stays visible with a description that says it has
no effect there. Task 2.3 decides which, on both versions.

### Decided in the detail link partial

`Profile/Item/DetailLink` renders nothing when `settings.detailLink` is
`none`, no `detailPid` is passed and the element is not the list-and-detail
element. The item, the header partials and every override of them stay as
they are, and a project that overrides only the name or the contracts gets the
behaviour without touching its files.

Rejected: no link whenever `detailPid` is `0`. It changes every site that
places the detail plugin on the page of its list and never set a detail page,
and nothing in a site's configuration says which sites do.

## Risks / Trade-offs

- [An override of the detail link partial ignores the settings] → The
  `Feature` entry shows the condition to add.
- [A site sets "no link" and has a detail page on another element] → The
  element choice "link", the list-and-detail element and passed detail pages
  keep linking.
