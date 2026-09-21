## Context

`packages/fgtclb/academic-partners/Resources/Private/TypeScript/frontend/map.ts`
on this branch:

```ts
document.addEventListener('DOMContentLoaded', (): void => {
    ...
    const latitude = Number(partner.dataset.lat);
    const longitude = Number(partner.dataset.lng);

    if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
```

Leaflet and its cluster plugin are vendored, minified classic scripts that
publish the global `LeafletObject`. They have no module specifier.

## Goals / Non-Goals

**Goals:**

- The map draws whenever the module runs.
- Only a partner with a usable location is drawn.

**Non-Goals:**

- Keeping an unlocated partner out of the page. That is the server half, and
  `proposal.md` says why it is not here.
- Anything about the tiles, the clustering or the popup markup.

## Decisions

### Ask the document rather than wait for it

`initializeMap()` is called immediately when `document.readyState` is not
`loading`, and deferred with `{ once: true }` while it is. That is the shape
`academic-study-plan.ts` already uses on this branch, so the two modules now
start the same way.

Rejected: rendering the module without `async`. The attribute is written
unconditionally by TYPO3's `JavaScriptRenderer` — `f:asset.module` takes an
identifier and nothing else — so it is not ours to choose.

### Test absence before the conversion

`Number('')` is `0`. The raw attribute is therefore trimmed and compared to the
empty string *before* it becomes a number, and `Number.isFinite()` replaces
`Number.isNaN()` so that `Infinity` is refused as well.

The pair 0/0 is refused whatever spelling it arrives in — `0`, `0.0`, `-0` —
because it means "nothing was written" rather than a place. A **single** zero is
kept deliberately: longitude 0 runs through the United Kingdom, France, Spain
and Ghana, and latitude 0 is the equator.

Rejected: refusing a single zero as well. It would hide a real partner on the
prime meridian to catch a case the pair check already catches.

### The console warning stays

An unusable record is an editorial mistake. The module reported it before and
still does; the test captures the warning rather than silencing it, and asserts
which partners were reported.

## Risks / Trade-offs

- [A partner at exactly 0/0] → Not a place anyone has an office. The trade is
  explicit, and the changelog entry states it.
- [The page still carries unlocated partners] → Named as a non-goal, in the
  proposal and in the changelog entry, rather than left to be discovered.

## Migration Plan

Nothing is required on update.

## Open Questions

None.
