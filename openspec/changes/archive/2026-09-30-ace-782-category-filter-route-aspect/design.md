## Context

See `proposal.md` for the motivation. `category_types` has no `Routing/`
class and registers no aspect. Core's `sys_category` TCA has no slug column
(checked in `cms-core/Configuration/TCA/sys_category.php`, v13.4.34), so
`PersistedAliasMapper` has nothing to map. `PersistedPatternMapper` maps one
record per value and has no notion of a list.

`AspectFactory::create()` instantiates every aspect with
`GeneralUtility::makeInstance($className, $settings)` on both v13.4.34 and
v14.3.7: an aspect is not built by the container and gets no constructor
injection. `SlugHelper` has the same constructor
(`string $tableName, string $fieldName, array $configuration, int $workspaceId = 0`)
and `sanitize()` on both versions.

The value this aspect maps is the flat filter argument of
`ace-723-list-filter-get-urls`: a comma separated uid list.

## Goals / Non-Goals

**Goals:**

- One aspect every list route enhancer can use, configured by category group.
- No schema change, and URLs that survive a category rename.

**Non-Goals:**

- Redirecting an outdated slug to the current one.
- Caching titles across requests.

## Decisions

### An own aspect class instead of extending PersistedPatternMapper

`FGTCLB\CategoryTypes\Routing\Aspect\CategoryFilterMapper` (final) implements
`PersistedMappableAspectInterface`, `SiteLanguageAwareInterface` and
`UnresolvedValueInterface`, using core's `SiteLanguageAccessorTrait` and
`UnresolvedValueTrait`. It is registered in `ext_localconf.php` as
`$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['CategoryFilterMapper']`.

Rejected: extending `PersistedPatternMapper`, as ace-demo does. Its
`generate()` and `resolve()` both work on exactly one record, so a list mapper
overrides both and reuses nothing but protected helpers whose signatures are
not a stable contract between core versions.

### Not static mappable, so the filter stays out of the page cache identifier

Revisited before implementing, as this section asked.
`PluginEnhancer::buildResult()` makes every argument whose aspect implements
`StaticMappableAspectInterface` a static route argument, and
`PrepareTypoScriptFrontendRendering` puts the static route arguments into the
page cache identifier unfiltered, on v13.4.34 and v14.3.7 alike. A static
filter would therefore bring back one page cache entry per combination of
categories, which `ace-723-list-filter-get-urls` removed on purpose by
excluding `tx_<extension>_<plugin>[demand]` from the cache hash.

The aspect is a plain mapper instead, so the filter is a dynamic argument.
The cache hash of a dynamic argument is built from its relevant parameters
only, and the page cache identifier takes dynamic arguments only when a cHash
is present. With the demand excluded, as the partner, project and program
lists do, the URL carries no cHash and the filter adds no cache entry. A
route whose argument is not excluded gets a cHash appended, as any dynamic
argument does. That is correct, just not readable.

Rejected: static mappable, as first designed. It drops the cHash for any
plugin, but at the cost the list plugins decided against.

### The slug is computed, the uid is the key

`generate()` loads the categories of the list in the site language (the
translation where one exists, following the fallback chain of the site
language, else the default record), and emits `SlugHelper::sanitize(title) .
'-' . uid` per uid, in list order, joined by commas. `SlugHelper` keeps
slashes, so a slash in a title becomes a dash first. An empty sanitised title
becomes `category`. A uid outside the group, or of a hidden or deleted
category, generates nothing, so the link falls back to the query string rather
than to a URL that would not resolve. `resolve()` splits on commas, reads the
trailing `-<uid>` of each part with a strict pattern, and checks existence,
visibility and membership of the uid in the group's category types. Any
unreadable part makes the whole segment unresolvable, so the route does not
match.

Rejected: a persisted slug column with `PersistedAliasMapper`. It needs a
schema change, DataHandler slug maintenance and collision handling for a URL
segment the uid already makes unique (ACE-623 keeps the uid for exactly that
reason: two categories can share a title).

### Dependencies without injection

Because aspects are not container-built, the class obtains `ConnectionPool`,
`SlugHelper` and `CategoryTypeRegistry` through
`GeneralUtility::makeInstance()` and keeps no state beyond its settings, the
type identifiers of its group and the site language the factory sets. The
registry is a public service already (`Configuration/Services.yaml`).

### Settings

- `group`, required. A missing or unknown group throws at construction, as
  core aspects do for missing settings, so a typo fails loudly instead of
  turning every filter URL into a 404.
- `emptyValue` (default `all`) and an optional `localeMap` of
  `locale`/`value` pairs, the shape `LocaleModifier` uses.

## Risks / Trade-offs

- [A rename generates a new URL while the old one still resolves] → a filter
  resolves under any slug and in any order of its parts, while every
  generated link uses the current title. The canonical URL is the list URL
  without the filter, so the variants do not compete. Named in the documentation.
- [One query per generated URL] → a list with pagination and tags generates
  a handful of links per request. Acceptable, and a per-request runtime cache
  can follow if measurements ask for it.
- [Workspaces preview] → `sys_category` is workspace aware in the core
  (`versioningWS`), but the aspect does no version overlay. With the frontend
  restrictions, a category created in the workspace is found, while changed
  and deleted ones keep their live state. In a workspace preview a category
  renamed in the workspace generates its live slug, and one deleted in the
  workspace still resolves. Live output is not affected.
