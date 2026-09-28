## Context

`main` (ACE-770, #790) reads `pages.show_on_map` in the map query, adds
`Partner::isShownOnMap()`, synchronizes the column with the default record and
renames the coordinates wizard to `SynchronizePartnerTranslationsUpgradeWizard`,
which copies the switch as well. The decisions there were:

- The map query reads the switch of the record in the language of the page, on
  a site with `fallbackType: fallback`, so the column is synchronized rather
  than excluded from translation.
- One wizard for both groups, since the release is not out yet, each group
  compared and detached on its own.

On this branch the map action sets `drawableOnly` after building the demand as
on `main`, but there is no demand event, no map partial and no page template
test with a map. The coordinates wizard is the same class with the issue
references of this branch (ACE-708, ACE-709) and a licence header.

## Goals / Non-Goals

**Goals:**

- The same rule as on `main`, on TYPO3 v12 and v13.

**Non-Goals:**

- A map partial on this branch.

## Decisions

### The code of `main`, adapted where the branch differs

The repository constraint, the model methods, the TCA and the wizard are taken
from `main`. The wizard keeps the licence header and names ACE-709 and ACE-708
where `main` names ACE-562. The wizard test and its fixture were identical on
both branches before the change and are taken over as they are on `main`.

The map plugin test of this branch gets the German language and the fixture
`partnerMapPageTranslated.csv` from `main`, for the two tests of the switch in
a translation.

The map query reads the switch of the page language on TYPO3 v12 as well: the
Extbase query parser of v12 selects the translated row when the page language
has one, as v13 does. The plugin tests pin it on both core versions.

The requirements are added to the existing capability
`academic-partners/partner-map` of this branch, which the ACE-709 backport
created. `main` has no such capability and created
`academic-partners/map-show-on-map` instead.

### An Important changelog entry

`Documentation/Changelog/2.4/Important-PartnerMapHonoursShowOnMap.rst` is the
entry of `main`, without the partial: a template that renders a map for one
partner checks `{partner.shownOnMap}`. The coordinates entry names the wizard
by its new title and points to the new method.

## Risks / Trade-offs

- [A site used the switch as a note without meaning it] → the partner leaves
  the map after the update. The changelog entry and its query are the remedy.

## Open Questions

None.
