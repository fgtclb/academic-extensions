## Context

Where contracts are rendered today:

- `Resources/Private/Partials/Profile/Contract/Item.html:14-19` loops over
  `profile.contracts` unless a single `contract` is passed (`:6-12`). The
  selected-contracts plugin and `academic_contacts4pages` pass one.
- `Partials/Profile/PublicProfile/Position.html:10-14` and `Contact.html:20`
  loop over `profile.contracts` for the detail view. Both are switched on by
  `special: datasFromContracts` in `Configuration/AcademicPersons/Settings.yaml`
  (`profile.details.position`, `profile.details.contact`, `:188-193` on
  `main` before the change).
- `Contract::$validFrom` and `$validTo` (`Classes/Domain/Model/Contract.php:25-26`)
  are not used by any frontend view of `academic_persons`: only by the backend
  label (`:314`) and by the editing frontend of `academic_persons_edit`, which
  lets a profile owner edit them.

The plugins read their unit and function type restriction from
`settings.organisationalUnits` and `settings.functionTypes`
(`ProfileController::adoptSettings()`). The card plugin uses the same
`List.xml` FlexForm, with fields disabled through
`Configuration/TSconfig/Card/page.tsconfig`.

`academic_persons` ships no ViewHelper, and no template declares a persons
ViewHelper namespace. The candidate assumed one to reuse. The namespace
`FGTCLB\AcademicPersons\ViewHelpers` is new. Other extensions of the
repository ship ViewHelpers of their own (`academic_base`, `academic_jobs`,
`academic_partners`, `academic_programs`, `academic_projects`,
`category_types`); none of them selects contracts.

## Goals / Non-Goals

**Goals:**

- One rule for "which contracts", evaluated in PHP and used by every template.
- Unchanged output with default settings.

**Non-Goals:**

- Restricting the lazy `profile.contracts` relation itself.

## Decisions

### A stateless selection service and a value object

- `FGTCLB\AcademicPersons\Service\ContractSelector`, `final readonly`, with
  `select(Profile $profile, ContractSelection $selection): ContractSelectionResult`.
  The result carries the selected contracts and the date on which the
  selection would change, see the page cache lifetime below.
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

`<persons:contracts profile="{profile}" settings="{settings}" />` returns the list,
used inline in `f:for`. The ViewHelper resolves the selector from the
container, so it has to be a public service. No `Services.yaml` entry is
needed for that: `EXT:fluid` autoconfigures every `ViewHelperInterface` with
the tag `fluid.viewhelper` and a compiler pass that makes it public and not
shared (`cms-fluid/Configuration/Services.php`, v13.4 and v14.3 alike). A
functional rendering proves the resolution.

Rejected: the controller assigning a per-profile map. It does not reach the
partials rendered by `academic_contacts4pages`, and for the detail view it
would duplicate the `publicProfile` structure.

### Where the settings live

- FlexForm `settings.contracts.display`, `settings.contracts.matchFilter` and
  `settings.contracts.onlyValid` go into `Core13/List.xml` and
  `Core14/List.xml`, with English and German labels.
- `SelectedProfiles.xml` gets `display` and `onlyValid` only. That FlexForm
  has no `settings.organisationalUnits` and no `settings.functionTypes`, so
  `matchFilter` would be a checkbox without effect there (verified on `main`
  before the apply; the proposal had listed all three).
- The card shares `List.xml`, but `ProfileController::cardAction()` builds its
  demand from `demand.profileList` alone and never applies the unit or
  function type fields, which were inert before this change. `matchFilter`
  would turn them into a filter on contracts only, so the card's page TSconfig
  disables it, next to the list fields it already disables (decided with the
  maintainer during the review). The two inert fields stay as they are; hiding
  them is not part of this change. Hiding does not clear a value: a content
  element switched from list to card keeps a stored "matching" and the card
  applies it, as it applies a detail page stored while it was a list. The
  configuration chapter says so.
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

The FlexForm fields sit on the plugin's first sheet. In `List.xml` they
follow `settings.functionTypes` rather than `settings.showFields`, because
`matchFilter` refers to the two fields above it. In `SelectedProfiles.xml`
they follow `settings.showFields`.

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

While a validity option applies, the `persons:contracts` ViewHelper restricts the
page cache lifetime to the seconds until the next validity boundary of the
contracts it evaluated. A boundary is the day after the end date of a
contract valid today, or the start date of a contract left out only because
it has not started yet - both among the contracts that pass the unit and
function type filter. "First" is applied after that, so the end of a valid
contract that "first" drops counts as well: it can only make the lifetime
shorter, never too long, and it keeps the rule one sentence. The validity columns are date-only (`type => datetime`,
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

The decision sheet answered this with "cap through
`ModifyCacheLifetimeForPageEvent` with the boundaries collected while
rendering" (D-101, B). The maintainer confirmed the transport below during the
review of the implementation. The cap is what is implemented; the event is not,
because a listener would need the boundaries handed over from the rendering
through a shared service, which is per-request state. The cache data
collector of the request is core's own request-scoped place for a lifetime
limit, and `restrictMaximumLifetime()` is its API for it.

Rejected:

- `ModifyCacheLifetimeForPageEvent` with the boundaries collected while
  rendering. The listener would need the boundaries of the rendered
  contracts, which is per-request state in a shared service, and the
  stateless service rule excludes it. This is the transport of D-101's answer
  B, replaced as described above; the cap itself is kept.
- A page cache listener that queries every contract boundary of the storage
  folders. It shortens the lifetime of pages by contracts they never render.
- No restriction, with validity taking effect at the next cache expiry.
  Without `config.cache_period` an expired contract stays visible for up to a
  day on TYPO3 v13.4 and for up to a year on v14.3: the default of
  `CacheLifetimeCalculator` is 86400 seconds on v13.4 and `365 * 86400` on
  v14.3 (verified in both trees while applying the change).

## Risks / Trade-offs

- A page renders many profiles with many contracts. → The boundary is
  computed from contracts the selector loads anyway; the restriction is one
  `min()` per rendering.
- A boundary lies in the past because the date aspect is simulated. → A
  simulated date is a backend preview, which is not cached; the ViewHelper
  never restricts to less than one second.

## Open Questions

None.
