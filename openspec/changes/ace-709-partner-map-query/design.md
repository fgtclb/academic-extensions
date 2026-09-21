## Context

See `proposal.md`. The relevant shapes on this branch:

- `PartnerRepository::findByDemand()` builds a `$constraints` array and combines
  it with `logicalAnd()`; `findGeoLocated()` requires a geocode *status* only.
- Both coordinate columns are nullable `VARCHAR(20)`; `Partner` types them as
  non-nullable `float`.
- `GeocodeCommand` persists through the Extbase repository.
- `Map.html` loads the Leaflet assets unconditionally and iterates `{partners}`.

## Goals / Non-Goals

**Goals:**

- Only a partner with a location reaches the map, in every language.
- A template that renders one partner can ask the same question.
- Existing translations are repaired once.

**Non-Goals:**

- Changing the column type, or `show_on_map`. See `proposal.md`.

## Decisions

### Absence is the criterion, not the value

`NULL` and the empty string mean "no coordinate". `NOT (column = '')` is `NULL`
for a `NULL` column and therefore not true, which excludes both without a second
comparison per column. A stored `0` is a real coordinate and only the pair `0/0`
is refused, because longitude 0 runs through four countries and latitude 0 is
the equator.

### The model and the query do not see the same thing, and say so

Extbase skips a `NULL` column and leaves the property at its default of `0`, and
casts the empty string to `0.0`. So `Partner::isDrawable()` cannot tell absence
from a stored zero, and a hand-entered `0.0` is refused there while the string
query lets it through. Both directions are documented on both sides rather than
papered over.

### The command writes through the DataHandler

Synchronization is a DataHandler feature, and geocoding is the one path that
writes coordinates in production. A repository write would have made the TCA
declaration apply to backend edits and to whatever the wizard caught, and to
nothing else. The CLI has no backend user, so `Service\GeocodeWriteContext`
supplies a synthetic one — three traps deep, all written down in
`docs/architecture/translation-synchronization.md`.

A refused write now fails the command. The queue selects on
`geocode_status = 'open'`, so a result that never reaches the database would
leave the status untouched and the next scheduled run would pick the same
partner again — an unbounded retry against a public geocoding service.

### `allowLanguageSynchronization`, not `l10n_mode => 'exclude'`

Both say the field is not translatable. `exclude` hides it from the editor,
which makes a deliberate exception impossible; synchronization keeps it visible
and lets an editor detach it, which the wizard then leaves alone.

### The wizard compares in PHP

One side of the comparison is regularly `NULL`, and a null safe comparison is
spelled differently on each of the four supported platforms. Partner pages are
counted in dozens, so reading them is cheaper than the portability problem.

## Risks / Trade-offs

- [A hand-entered `0.0` still reaches the page] → The module drops it, and the
  changelog entry says so. Removing the asymmetry means changing the column
  type, which is not this change.
- [An installation with a replaced `Map.html`] → Keeps its own copy and gets
  neither the empty state nor the conditional assets. The query change reaches
  it regardless, which is the half that matters.

## Migration Plan

Run the upgrade wizard once after updating. Nothing else is required.

## Open Questions

None.
