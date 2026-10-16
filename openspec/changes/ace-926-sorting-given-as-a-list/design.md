## Context

See proposal.md for the motivation. Each list extension builds its demand in
one factory from the settings of the element and from the `demand` argument of
the request. Extbase hands that argument over as it came, nested arrays
included, and the demand arguments are excluded from the cache hash on this
branch, so a GET reaches the factory as well as the POST that the redirect
handler reads from the parsed body. The setters of the three demand objects
declare `string` parameters and the factories run with `strict_types`, so an
array ends in a `TypeError`. Every setter already ignores a string that is not
one of its options. A setting arrives as an array once TypoScript gives it a
child, and Extbase keeps that array when the element stores a value:
`ArrayUtility::mergeRecursiveWithOverrule()` does not replace an array with a
string, on TYPO3 v12 to v14.

## Goals / Non-Goals

**Goals:** one guard per factory, at the place where a value leaves the
untyped array, that makes a value that is not a string behave exactly like an
unknown string.

**Non-Goals:** typed setters that accept `mixed`, or a validation of the whole
demand argument.

## Decisions

- **Map a value that is not a string to the empty string, then call the setter
  as before.** No setter accepts the empty string, so the value is ignored by
  the same code that ignores an unknown option, and the branches of the
  factories (combined sorting first, then field and direction, in
  `academic_programs` the fallback to the setting for a demand without any
  sorting) stay untouched. The alternative, treating such a value as absent,
  would let a `sorting` given as a list fall through to `sortingField` and to
  the program finder fallback, a second behaviour for one malformed input.
- **A private method per factory, not a shared helper.** The three factories
  share `CategoryFilterNormalizer` of `category_types`, which would be the
  place for a shared one, but a one-line type check does not justify widening
  an `@internal` API across packages.
- **The project settings go through the same method.** `settings.sorting`
  replaces the unguarded `setSorting()` call, and `settings.activeState`
  replaces a `(string)` cast that turned a list into `Array` with a PHP
  warning. Partners and programs guard their setting already in
  `applySortingFromSettings()` (ACE-914).
- **Ordinary method calls, no core version switch.** Nothing involved differs
  between TYPO3 v13 and v14.

## Risks / Trade-offs

- [An integer setting given by PHP, not by TypoScript or a FlexForm, is now
  ignored for the active state instead of being cast] → Extbase settings and
  FlexForm values are strings, and an integer is no active state either.

## Migration Plan

None. The change only turns an error into the default behaviour.
