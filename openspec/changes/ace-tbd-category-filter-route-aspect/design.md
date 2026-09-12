## Context

See `proposal.md` for the motivation. `category_types` has no `Routing/`
class and registers no aspect. Core's `sys_category` TCA has no slug column
(checked in `cms-core/Configuration/TCA/sys_category.php`, v13.4.35), so
`PersistedAliasMapper` has nothing to map. `PersistedPatternMapper` maps one
record per value and has no notion of a list.

`AspectFactory::create()` instantiates every aspect with
`GeneralUtility::makeInstance($className, $settings)` on both v13.4.35 and
v14.3.6: an aspect is not built by the container and gets no constructor
injection. `SlugHelper` has the same constructor
(`string $tableName, string $fieldName, array $configuration, int $workspaceId = 0`)
and `sanitize()` on both versions.

The value this aspect maps is the flat filter argument of
`ace-tbd-list-filter-get-urls`: a comma separated uid list.

## Goals / Non-Goals

**Goals:**

- One aspect every list route enhancer can use, configured by category group.
- No schema change; URLs that survive a category rename.

**Non-Goals:**

- Redirecting an outdated slug to the current one.
- Caching titles across requests.

## Decisions

### An own aspect class instead of extending PersistedPatternMapper

`FGTCLB\CategoryTypes\Routing\Aspect\CategoryFilterMapper` (final) implements
`PersistedMappableAspectInterface`, `StaticMappableAspectInterface`,
`SiteLanguageAwareInterface` and `UnresolvedValueInterface`, using core's
`SiteLanguageAccessorTrait` and `UnresolvedValueTrait`. It is registered in
`ext_localconf.php` as
`$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['CategoryFilterMapper']`.

Rejected: extending `PersistedPatternMapper`, as ace-demo does. Its
`generate()` and `resolve()` both work on exactly one record, so a list mapper
overrides both and reuses nothing but protected helpers whose signatures are
not a stable contract between core versions.

### Static mappable, so the URL has no cache hash

Like `PersistedPatternMapper`, the aspect declares itself static mappable: the
value space is bounded, because `resolve()` only accepts uids of existing
categories of the configured group. The enhancer can then drop the cache hash
for the argument, which is the point of a readable URL.

### The slug is computed, the uid is the key

`generate()` loads the categories of the list in the site language (the
translation where one exists, else the default record), and emits
`SlugHelper::sanitize(title) . '-' . uid` per uid, in list order, joined by
commas. An empty sanitised title becomes `category`. `resolve()` splits on
commas, reads the trailing `-<uid>` of each part with a strict pattern, and
checks existence, visibility and membership of the uid in the group's
category types. Any unreadable part makes the whole segment unresolvable,
so the route does not match.

Rejected: a persisted slug column with `PersistedAliasMapper`. It needs a
schema change, DataHandler slug maintenance and collision handling for a URL
segment the uid already makes unique (ACE-623 keeps the uid for exactly that
reason: two categories can share a title).

### Dependencies without injection

Because aspects are not container-built, the class obtains `ConnectionPool`,
`SlugHelper` and `CategoryTypeRegistry` through
`GeneralUtility::makeInstance()` at call time and keeps no state beyond its
settings and the site language the factory sets. The registry must be a
public service for that; if it is not, it is made public in
`Configuration/Services.yaml`, the style the extension uses.

### Settings

- `group` (required; a missing group throws at construction, as core aspects
  do for missing settings);
- `emptyValue` (default `all`) and an optional `localeMap` of
  `locale`/`value` pairs, the shape `LocaleModifier` uses.

## Risks / Trade-offs

- [A rename generates a new URL while the old one still resolves] → two URLs
  for one list; the page's canonical tag points at the generated one. Named
  in the documentation.
- [One query per generated URL] → a list with pagination and tags generates
  a handful of links per request; acceptable, and a per-request runtime cache
  can follow if measurements ask for it.
- [Workspaces preview] → categories are not versioned in these extensions;
  the query uses the default frontend restrictions.
