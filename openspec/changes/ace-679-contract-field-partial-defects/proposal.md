## Why

The profile list, list and detail, card, selected profiles and selected
contracts plugins of `academic_persons`
(`packages/fgtclb/academic-persons`) render contract fields through one shared
partial. Two of the fields that partial is handed are rendered wrong. The
"organisational unit" item an editor can tick in `showFields` produces a row
without a value, and without a label either, because the label key does not
exist. A phone number link keeps the spaces of the stored number in its `tel:`
target, in the shared partial and in the profile detail template alike.
Projects override the partial to correct both.

This is the backport of ACE-679, whose implementation on `main` is
pull request #659, re-derived for TYPO3 v12 and v13.

## What Changes

- An organisational unit selected in `showFields` renders its display text,
  falling back to the unit name, under a translated label (English and German).
- A phone number link uses the number without spaces as its `tel:` target, in
  the shared contract field partial and in the profile detail template. The
  visible text keeps the stored spelling.
- No new items in `showFields`.

The behaviour is identical on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `academic-persons/contract-field-rendering`: what the contract fields
  selected in `showFields` render in the list, list-and-detail, card,
  selected-profiles and selected-contracts plugins.

### Modified Capabilities

None.

## Impact

- `academic_persons`: the contract field partial, the profile detail template,
  and the two language files of the frontend labels.
- A new functional test class for the selected contracts plugin rendering the
  shipped templates, and its fixture.
- No schema, TCA, dependency or API change.

## Non-goals

- The phone link target prefix of the `main` change. It is a feature and stays
  on `main`.
- Normalising anything beyond spaces in the `tel:` target.
- A `showFields` item for the function type.
- Restyling the partial.

## Source

Backport of ACE-679 from `main`, re-derived from the file-level backport
analysis rather than cherry-picked. The `main` detail view had already stripped
the spaces; the older detail template of this branch had not, so the correction
touches one file more here.
