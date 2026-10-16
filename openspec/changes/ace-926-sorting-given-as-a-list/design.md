## Context

See proposal.md for the motivation. Each list extension builds its demand in
one factory from the settings of the element and from the `demand` argument of
the request. Extbase hands that argument over as it came, nested arrays
included, on TYPO3 v12 and v13 alike. On this branch the filter form posts to
the list action, and the partner map reads the same demand in its own action,
so a submission reaches the factory directly. A link reaches it only with the
cache hash of its arguments, as every installation the installer creates
enforces it, or on a site that switched the enforcement off. The setters of
the three demand objects declare `string` parameters and the factories run
with `strict_types`, so an array ends in a `TypeError`. Every setter already
ignores a string that is not one of its options. A setting arrives as an array
once TypoScript gives it a child, and Extbase keeps that array when the
element stores a value: `ArrayUtility::mergeRecursiveWithOverrule()` does not
replace an array with a string, on TYPO3 v12 and v13.

The factories, the demand objects and their setters are the same code as on
`main` apart from what `main` added for the URLs of a filtered list, so the
guard is the same change.

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
  factories (combined sorting first, then field and direction) stay untouched.
  The alternative, treating such a value as absent, would let a `sorting`
  given as a list fall through to `sortingField`, a second behaviour for one
  malformed input.
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
  between TYPO3 v12 and v13.
- **Functional tests of their own.** This branch has no `FilterUrlTest`, where
  `main` adds its cases. A new `Academic*DemandValueNotAStringTest` per
  extension posts the list to the page and requests it with a link that
  carries the cache hash of its arguments. The project test also renders an
  element without settings and one that stores the sorting title, Z to A, and
  the active projects, below TypoScript that gives the sorting and the active
  state a value and a child, which covers the settings end to end. `main` has
  a test class of its own for that, here it shares the fixture of the request
  cases.

## Risks / Trade-offs

- [An integer setting given by PHP, not by TypoScript or a FlexForm, is now
  ignored for the active state instead of being cast] → Extbase settings and
  FlexForm values are strings, and an integer is no active state either.

## Migration Plan

None. The change only turns an error into the default behaviour.
