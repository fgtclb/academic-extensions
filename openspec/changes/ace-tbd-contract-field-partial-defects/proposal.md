## Why

The profile list, card and selected-profiles plugins of `academic_persons`
(`packages/fgtclb/academic-persons`) render contract fields through one shared
partial. Two of the fields that partial is handed are rendered wrong. The
"organisational unit" item the editor can tick in `showFields` produces a row
without a value, and without a label either, because the label key does not
exist. The phone number link keeps the spaces of the stored number in its
`tel:` target, which the detail view already strips. Projects override the whole
partial to correct both.

## What Changes

- An organisational unit selected in `showFields` renders its display text,
  falling back to the unit name, under a translated label (English and German).
- A phone number link in the list, card and selected-profiles output uses the
  number without spaces as its `tel:` target, as the detail view does. The
  visible text keeps the stored spelling.
- A new site setting holds a prefix that is prepended to every `tel:` target
  of the persons output, for installations that store phone numbers as
  extensions only. It is empty by default, which keeps the targets above.
  The visible text never shows the prefix.
- No new items in `showFields`.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/contract-field-rendering`: what the contract fields
  selected in `showFields` render in the list, list-and-detail, card,
  selected-profiles and selected-contracts plugins.

### Modified Capabilities

None.

## Impact

- `academic_persons`: the contract field partial, the contact partial of the
  detail view, the two language files of the frontend labels, one site
  setting in the site set and the static template, and a `Feature-*.rst`
  for the setting.
- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`) renders
  the same partial through the profile item and gains the same corrections.
  Its TypoScript setup maps the new prefix setting into its plugin settings.
- A new functional test fixture for the card plugin.
- No schema, TCA, dependency or API change.

## Non-goals

- A `showFields` item for the function type (part of a separate detail and
  function type change).
- A prefix that applies only to some numbers, for example those without a
  leading `+` or `0`.
- Icons in the list and card contract rows.
- Restyling the partial.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-02`). Two of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-contract-field-partial-defects` when the issue is filed after
implementation.
