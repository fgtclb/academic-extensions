## Context

Where contracts are rendered today:

- `Resources/Private/Partials/Profile/Contract/Item.html:14-19` loops over
  `profile.contracts` unless a single `contract` is passed (`:6-12`). The
  selected-contracts plugin and `academic_contacts4pages` pass one.
- `Partials/Profile/PublicProfile/Position.html:10-14` and `Contact.html:20`
  loop over `profile.contracts` for the detail view. Both are switched on by
  `special: datasFromContracts` in `Configuration/AcademicPersons/Settings.yaml`
  (`profile.details.position`, `profile.details.contact`, `:186-191`).
- `Contract::$validFrom` and `$validTo` (`Classes/Domain/Model/Contract.php:24-25`)
  are used only in the backend label (`:314`).

The plugins read their unit and function type restriction from
`settings.organisationalUnits` and `settings.functionTypes`
(`ProfileController::adoptSettings()`). The card plugin uses the same
`List.xml` FlexForm, with fields disabled through
`Configuration/TSconfig/Card/page.tsconfig`.

`academic_persons` ships no ViewHelper, and no template declares a persons
ViewHelper namespace. The candidate assumed one to reuse. The namespace
`FGTCLB\AcademicPersons\ViewHelpers` is new; `academic_base` has the only
academic ViewHelper (`ValidationEnsureViewHelper`).

## Goals / Non-Goals

**Goals:**

- One rule for "which contracts", evaluated in PHP and used by every template.
- Unchanged output with default settings.

**Non-Goals:**

- Restricting the lazy `profile.contracts` relation itself.

## Decisions

### A stateless selection service and a value object

- `FGTCLB\AcademicPersons\Service\ContractSelector`, `final readonly`, with
  `select(Profile $profile, ContractSelection $selection): list<Contract>`.
- `ContractSelection` is a `final readonly` value object built from plugin
  settings, or from the detail block settings, by named constructors.
- The display mode is a string-backed enum `ContractDisplay` (`all`, `first`).
- The selector applies the unit and function type filter first, then validity,
  then `first`.
- "Today" comes from the `date` aspect of the injected `Context`, so a
  simulated date in the backend preview applies.

Rejected:

- Filtering in Fluid, as six projects do. The counts are wrong, and "first"
  depends on the filter.
- An SQL restriction of `profile.contracts`. Extbase cannot constrain a lazy
  relation per parent without custom mapping.
- A method on `Profile`. It would need plugin settings on an entity.

### A ViewHelper as the template entry point

`<p:contracts profile="{profile}" settings="{settings}" />` returns the list,
used inline in `f:for`. The ViewHelper resolves the selector from the
container, so it is registered as a public service. Registration follows the
extension's existing `Services.yaml` style.

Rejected: the controller assigning a per-profile map. It does not reach the
partials rendered by `academic_contacts4pages`, and for the detail view it
would duplicate the `publicProfile` structure.

### Where the settings live

- FlexForm `settings.contracts.display`, `settings.contracts.matchFilter` and
  `settings.contracts.onlyValid` go into `Core13/List.xml`, `Core14/List.xml`
  and `SelectedProfiles.xml`, with English and German labels.
- `SelectedContracts.xml` is not touched: a chosen contract is rendered as
  chosen. The candidate listed it; that is corrected here.
- For the detail view, `profile.details.position.contracts` and
  `profile.details.contact.contracts` (`all`|`first`) in `Settings.yaml`, next
  to `special`, and `profile.details.position.onlyValid` and
  `profile.details.contact.onlyValid` (bool, default false) next to them. The
  settings normaliser accepts and defaults all four.

### Templates

`Contract/Item.html` loops over the selection when no `contract` is passed.
`Position.html` and `Contact.html` loop over the selection of their block.

### Backend fields

The three FlexForm fields sit on the plugin's first sheet, after
`settings.showFields`.

Guessed layout — a sketch, not a design:

```text
Plugin > Contracts (GUESSED)
+--------------------------------------------------+
| Contracts to show      ( ) all   (o) first       |
| [x] only contracts of the selected org units     |
| [x] only contracts valid today                   |
+--------------------------------------------------+
```

### Decided: an empty end date stays open-ended, without a setting

A contract without `validTo` stays valid, and no setting treats an empty end
date as "not valid".

The one analysed project with the stricter rule applies it only to the
contracts its campus management import writes, which a site-wide setting
cannot express. That rule belongs where it is today: in the import, by
writing the end date, or in the project's template.

### Decided: the detail blocks offer "only valid today" as well

`profile.details.position.onlyValid` and `profile.details.contact.onlyValid`
(bool, default false) sit next to `contracts` in `Settings.yaml` and reach
the selector through the detail block's `ContractSelection`.

The selector implements validity already, so this is one normaliser key per
block. Without it, a list hides an expired contract that the same person's
detail page still shows.

### Decided: the page cache lifetime ends at the next validity boundary

While a validity option applies, the `p:contracts` ViewHelper restricts the
page cache lifetime to the seconds until the next validity boundary of the
contracts it evaluated. A boundary is the day after the end date of a shown
contract, or the start date of a contract left out only because it has not
started yet. The validity columns are date-only (`type => datetime`,
`format => date`), so every boundary falls at midnight, computed from the
same `date` aspect as "today".

The ViewHelper takes the request the rendering context carries as its
`ServerRequestInterface` attribute (set on v13 and v14), reads the request
attribute `frontend.cache.collector` and calls
`CacheDataCollector::restrictMaximumLifetime()` on it. Both exist on v13 and
v14 (TYPO3 v13.3, Feature #102422). The selector returns the boundary as a
second pure result next to the selection. No service keeps state: the
collector is the request's own object. Without the attribute, for example
outside a frontend page rendering, nothing is restricted. Without a validity
option the output does not depend on the date, and nothing is restricted
either.

The start date of a contract that is still hidden counts as a boundary as
well, next to the end date of a shown one. Without it, a contract that
starts tomorrow would stay invisible until the regular cache expiry, which
is the defect the restriction exists to prevent.

Rejected:

- `ModifyCacheLifetimeForPageEvent` with the boundaries collected while
  rendering. The listener would need the boundaries of the rendered
  contracts, which is per-request state in a shared service, and the
  stateless service rule excludes it.
- A page cache listener that queries every contract boundary of the storage
  folders. It shortens the lifetime of pages by contracts they never render.
- No restriction, with validity taking effect at the next cache expiry. With
  the default `cache_period` of 24 hours an expired contract stays visible
  for up to a day.

## Risks / Trade-offs

- A page renders many profiles with many contracts. → The boundary is
  computed from contracts the selector loads anyway; the restriction is one
  `min()` per rendering.
- A boundary lies in the past because the date aspect is simulated. → A
  simulated date is a backend preview, which is not cached; the ViewHelper
  never restricts to less than one second.

## Open Questions

None.
