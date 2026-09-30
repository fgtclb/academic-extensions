# Category filter routing

`category_types` ships the routing aspect type `CategoryFilterMapper`. It maps
the category filter argument of the partner, project and program lists, one
comma separated list of category uids (see
[List filter URLs](list-filter-urls.md#the-demand-in-the-url)), to a readable
path segment and back: `2,6` becomes `americas-2,europe-6` on an English page
and `amerika-2,europa-6` on a German one. How an integrator configures it is in
the Developers chapter of the `category_types` manual. This page is about why
it is built the way it is.

The class is
`packages/fgtclb/typo3-category-types/Classes/Routing/Aspect/CategoryFilterMapper.php`,
registered in the `ext_localconf.php` of the extension as
`$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['CategoryFilterMapper']`.
The aspect type and its settings are public API, the class is not.

## Why not a core mapper

- `PersistedAliasMapper` needs a slug field, and `sys_category` has none in
  the core. Adding one means a schema change, slug maintenance on every save
  and collision handling, for a segment the uid already makes unique.
- `PersistedPatternMapper` could build `{title}-{uid}`, but its `generate()`
  and `resolve()` both map exactly one record, and the filter is a list.
  Extending it would override both methods and reuse nothing but protected
  helpers, which are not API.

## The segment

- **One part per category, `<slug>-<uid>`, joined by commas**, in the order of
  the filter argument, which the lists write in ascending uid order.
- **The slug follows the language of the URL.** The title is read from the
  visible translation of the site language, then of each language in its
  fallback chain, then from the default-language record. A category for all
  languages has one title everywhere. `SlugHelper::sanitize()` makes the slug,
  after a slash has become a dash, because the slug helper keeps slashes. A
  title that sanitises to nothing becomes `category`.
- **Only the uid is read on resolve.** A part must look like a slug followed
  by `-<uid>`, and the uid must be a visible category of a type of the
  configured group, in the default language or for all languages. A uid above
  2147483647 is rejected before it reaches the database, since PostgreSQL
  refuses such a number for an integer column. One part that fails makes the
  whole segment fail, so the route does not match.
- **What cannot be mapped generates nothing.** A hidden, deleted or foreign
  category, an unknown uid or a value that is not a uid list makes
  `generate()` return `null`, so the link keeps its query argument instead of
  a path that would not resolve.
- **An empty filter value is a token of its own**, `all` by default, per
  language through a `localeMap` matched the way core's `LocaleModifier`
  matches it, the first matching item winning. The token resolves in its own
  language only. A token with a slash or a comma, or one that reads as a
  category part such as `all-5`, is refused when the aspect is built.
- **The lists never generate the token.** `createDemandArguments()` leaves an
  empty filter out, and `ExtbasePluginEnhancer::enhanceForGeneration()` skips a
  route whose variable is missing. A request that carries the token anyway has
  an empty filter, which keeps the preset categories of the content element
  from applying, as any demand argument does.

The uid stays in the segment for two reasons. Two categories may share a title,
and a URL keeps resolving after a category was renamed, since the title part is
never compared. The price is that a filter resolves under any slug and in any
order of its parts. The canonical URL of EXT:seo is the list URL without the
filter, because the demand is excluded from the cache hash, so these variants do
not compete in search engines.

## Not static mappable

The first design made the aspect static mappable, as core's persisted mappers
are, so the enhancer would drop the `cHash`. It was changed before
implementation:

- `PluginEnhancer::buildResult()` makes every argument whose aspect implements
  `StaticMappableAspectInterface` a static route argument.
- `PrepareTypoScriptFrontendRendering` puts the static route arguments into the
  page cache identifier unfiltered, on TYPO3 v13.4 and v14.3 alike.
- The lists keep their demand out of the cache hash precisely so that a visitor
  cannot create one page cache entry per combination of categories, see
  [List filter URLs](list-filter-urls.md#the-demand-is-not-part-of-the-cache-hash).
  A static filter would bring that back through the route.

As a plain mapper, the filter is a dynamic argument. The cache hash of a dynamic
argument is built from its relevant parameters only, the demand is excluded, so
the path carries no `cHash`, and the page cache identifier takes dynamic
arguments only when a `cHash` is present. Every filter path of a page shares one
cache entry, exactly as the query argument URLs do. A plugin that does not
exclude the argument gets a `cHash` appended to the path, which is correct, just
not readable.

## Built by the aspect factory

Core's `AspectFactory` creates every aspect with
`GeneralUtility::makeInstance($className, $settings)` on both core versions,
never through the container. The class therefore fetches `ConnectionPool`,
`SlugHelper` and `CategoryTypeRegistry` with `makeInstance()` itself, keeps no
state beyond its settings, the type identifiers of its group and the site
language the factory sets, and carries `#[Exclude]` so that the container does
not try to autowire its settings array.

The group is checked when the aspect is built. A missing group, or one without
category types, throws, as the core aspects do for a missing setting, so that a
typing error in a site configuration fails loudly instead of turning every
filter URL into a 404.

## Tests

- `typo3-category-types/Tests/Functional/Routing/Aspect/CategoryFilterMapperTest`
  builds the aspect through `AspectFactory` for three languages of a site and
  covers the settings, generation, the round trip, the empty token and every
  segment that must not resolve.
- `academic-partners/Tests/Functional/Plugins/AcademicPartnersFilterRouteTest`
  puts the partner list behind an enhancer with the aspect: the generated path
  without `cHash`, the filtered list rendered from it in two languages, the
  redirect of the filter form to the path, a 404 for a category of another
  group, and one page cache entry for two filter paths. That last test is the
  one that fails when the aspect is made static mappable.

## See also

- [List filter URLs](list-filter-urls.md): the filter argument this aspect
  maps, and why the demand is not part of the cache hash.
- [Database queries](database-queries.md): the quoting rules the queries of
  the aspect follow.
