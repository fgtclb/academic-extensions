## Context

See `proposal.md` for the motivation. On `main` (`428cf1a32`), in
`packages/fgtclb/typo3-category-types` unless a path says otherwise:

- `Classes/ServiceProvider.php:65-107` registers every type icon and every
  group icon with a file in the core `IconRegistry`, from a closure on
  `BootCompletedEvent` (`:109-113`). It takes the provider from
  `IconRegistry::detectIconProvider()` and swaps in `CurrentColorSvgIconProvider`
  of `academic_base` when the declaration sets `inlineIcon` and the file is an
  SVG. `registerIcon()` overwrites, and the closure runs after core merged every
  `Icons.php`, so a site package cannot replace a category type icon. A type
  without `icon` is registered with an empty source and `BitmapIconProvider`,
  which throws 1440754980 when it is rendered (`BitmapIconProvider.php:43` on
  13.4.35, the same check on 14.3.7).
- `detectIconProvider()` is `str_ends_with(strtolower($file), 'svg')`, identical
  on both cores (`IconRegistry.php:552-558` on v13, `:551-557` on v14).
- `CategoryType` (`@api`, readonly constructor) and `CategoryTypeGroup`
  (mutable, setters) list their fields explicitly in `fromArray()`,
  `toArray()`, `__set_state()` and, for the type, `jsonSerialize()`. The loader
  caches both under the fixed keys `CategoryTypes_Types` and
  `CategoryTypes_Groups` of `cache.core` and restores them through
  `__set_state()`.
- `useExisting` merges with `array_merge($existing->toArray(), $override)`
  (`Classes/Loader/CategoryTypeLoader.php:116-119`). Groups merge key by key and
  ignore an empty `icon` (`:159-170`). Both keep `inlineIcon` when an override
  names a new file without it. That is documented
  (`Documentation/Developers/CategoryTypes/Index.rst:174-179`) and pinned by
  `Tests/Unit/Loader/CategoryTypeLoaderTest.php` (comments at `:194-197` and
  `:204-206`), and it came with ACE-523, unreleased.
- `CategoryTypeGroup::getIconIdentifier()` returns
  `category_types.group.<identifier>` (`CategoryTypeGroup.php:73-76`). It came
  with ACE-364 (`c1ddb3a01`, 2026-09-27), is on no tag and not on branch `2`.
  No template renders a group icon.
- `ace-tbd-frontend-icon-registry` gives `academic_base` the frontend registry:
  `Configuration/FrontendIcons.php` per package, the event
  `CollectFrontendIconsEvent::addIcon(string $identifier, string $providerClass,
  array $options)` dispatched while the registry is built and cached, file
  entries applied after the event, and the `default-not-found` drawing for an
  unknown identifier.
- The frontend still renders category type icons with `core:icon` in six
  partials of partners and projects and through `ProgramFact.php:39` of
  programs, until `ace-tbd-programs-partners-projects-frontend-icons`.

## Goals / Non-Goals

**Goals:**

- Both registries carry every type and group icon, the frontend one without
  touching the core registry while it is built.
- One place decides the provider, and one place decides the frontend file and
  flag.

**Non-Goals:**

- Any change to what the backend shows, apart from the group identifier and
  the override rule below.

## Decisions

### Contributing through the event of `academic_base`

A `final` listener `EventListener\AddCategoryTypeFrontendIcons` (`@internal`)
with TYPO3's `#[AsEventListener]`, injecting `CategoryTypeRegistry`, adds one
entry per type and per group that has a frontend file. It holds no state and
runs once per build of the frontend registry, so it costs nothing per request.

Rejected: a second `registerIcon()` in the `BootCompletedEvent` closure. It
runs on every request, needs a mutable registry the frontend registry does not
offer, and would overwrite a site package entry exactly as the core
registration does. Rejected: a service provider extension or a tagged
contributor interface. The registry change fixes the event as its one
contribution API, and a second mechanism would need its own ordering rule.

### The backend registration stays on `BootCompletedEvent`

It is the backend registry and out of scope. Until the templates switch, the
frontend still needs the core registration of every type, so it cannot shrink
here. Moving it to a service provider extension of `IconRegistry` would stop
instantiating the core registry on frontend requests for these icons, but it
also changes when a site package's `Icons.php` wins, and whether anything else
instantiates the registry on a frontend request was not verified. That is a
change of its own, after the template switch. The closure changes in two
lines only: the group identifier, and the provider taken from the resolver
below, with the same result.

### One provider rule for both registries

A `final readonly` resolver `Imaging\CategoryTypeIconProviderResolver`
(`@internal`) answers the provider for a file and a flag: core's SVG suffix
rule, `CurrentColorSvgIconProvider` for an SVG with the flag. Both the closure
and the listener use it.

Rejected: `IconRegistry::detectIconProvider()` in the listener. It
instantiates the core registry, with the whole core icon set, while the
frontend registry is built, which is what the frontend registry exists to
avoid. Copying the one line keeps no state and both cores share it.

### Declared values in the model, the fallback on read

Both models gain `frontendIcon` (`''` when not declared) and
`frontendInlineIcon` (`?bool`, `null` when not declared), appended as optional
constructor parameters of the `@api` `CategoryType`, so no caller breaks.
`toArray()`, `fromArray()`, `__set_state()` and `jsonSerialize()` carry the
declared values. `getFrontendIcon()` and `isFrontendInlineIcon()` resolve the
rule of the spec. A cache entry written before the update restores with the
defaults, which reproduce today's frontend.

Rejected: resolving the fallback in the loader and storing the result. An
override of `icon` would then no longer reach a type that falls back, because
the merge would see a frozen copy of the old file.

### The inline flag belongs to the file next to it

An undeclared `frontendInlineIcon` follows `inlineIcon` only while the
frontend shows `icon`. A declared `frontendIcon` without its flag is shown as
an image. A declared `frontendInlineIcon` applies to whatever file the
frontend shows.

Rejected: independent fallbacks (`frontendInlineIcon ?? inlineIcon`). They
inline a file nobody opted in for, against the reason inlining is opt in
(`Documentation/Developers/Icons/Index.rst:96-122`). Rejected: a nested
`frontend:` block. The keys are flat by decision, and the group merge would
still need the same rule.

### An override that names a frontend file resets the frontend flag

Before the merge, an override with `frontendIcon` and without
`frontendInlineIcon` gets `frontendInlineIcon: null`, so the new frontend file
does not inherit the flag of the file it replaces. The group merge does the
same when it takes a non-empty `frontendIcon`. An override that names no file
keeps both flags, so `CategoryTypeLoaderTest.php:194-197` stays.

The rule for `icon` and `inlineIcon` stays as ACE-523 documented it: an
override of `icon` keeps the earlier `inlineIcon`. That behaviour is the same
in the backend and in the frontend today, so this change adds no new risk by
leaving it, and changing it is a decision of its own.

Rejected: resetting `inlineIcon` as well. It closes the same pairing gap for
`icon`, but it reverses a documented, unreleased 3.0 rule and its tests, which
is outside what this change is asked to do. Recorded for the maintainer as an
open decision. Rejected: keeping the flat merge for the new keys too. The
frontend keys are new, so they can start with the safer rule.

A type override `frontendIcon: ''` clears the frontend file through
`array_merge()`, while the group merge ignores an empty string, as it does for
`icon` today. Accepted, not widened.

### `category_types_group.<group>` for group icons

Every type icon identifier starts with the fifteen characters
`category_types.`, every group icon identifier with `category_types_group.`,
whose fifteenth character is `_`. Two strings that differ at one position are
different, so no group or type name can make them equal, dots included, which
the loader still accepts (`@todo` at `CategoryTypeLoader.php:77,86`). The
rule "a group must not be named `group`" goes away.

Rejected: `category_types.<group>`. It collides as soon as a group name holds
a dot (group `a.b` against type `b` of group `a`) and so needs the dot
validation, which turns accepted declarations into exceptions. Rejected: the
`tx-<extkey>-<group>-<name>` scheme of the icon consolidation pull request.
That pull request is rebased after this round and renames there, and its
mapping of underscores to hyphens is not injective. No Breaking entry: the
identifier was never released, so the four unreleased entries are amended.

### No frontend entry without a file

The listener skips a type or group whose frontend file is empty. The frontend
then answers with the not-found drawing of the registry instead of the
exception the core provider throws for an empty source. The backend keeps
today's registration (non-goal).

### Fixtures

A new fixture extension `test_category_types_frontend_icons`
(`tests/category-types-frontend-icons`) with the frontend cases, a type
without a file, the group `group`, and a `Configuration/FrontendIcons.php`
replacing one type icon. The four branch fixture `test_category_types_icons`
stays as it is, apart from the group identifier in its test. The override
cases are unit fixtures of the loader under `Tests/Unit/Fixtures/Packages/`.

## Risks / Trade-offs

- [The registry change lands with a different event API] → Task 1.1 re-reads
  it before anything is built on it.
- [A 3.0 development state relies on the kept `inlineIcon`] → Unreleased, the
  `Feature` entry on inlined icons is amended, and none of the seven analysed
  projects uses `useExisting` or `inlineIcon`.
- [An override of `icon` no longer reaches a frontend with its own file] →
  Specified and documented, `FrontendIcons.php` is the frontend only way.
- [A type without a file still throws when the backend or a `core:icon`
  template renders it] → Named, not fixed here, an issue of its own.
- [A template on `core:icon` does not see a `FrontendIcons.php` replacement]
  → Ends with the template switch of the follow-up change.

## Migration Plan

Flush the caches after the update, as after every update. No database
change. A rollback is a revert. Cache entries of the new shape restore in the
old models without the two keys.
