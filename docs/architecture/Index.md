# Architecture

The rules the code follows, and the reasoning behind them. Where the code base
does not yet follow a rule consistently, the page says so rather than
describing an intention as if it were the state.

## The short version

- A difference between core versions is a **switch** while it is one or two
  lines, and a **class split** only when a whole class has to differ.
- Services are **stateless**. New services must be; existing ones must not gain
  state.
- Never hand a raw array to `in()` or `notIn()`, build a constraint on the
  query builder that executes it, and order every result a caller renders or
  limits. All three rules come from defects that reached a release.
- One YAML in `academic_persons` drives field validation for **both** the backend
  FormEngine and the frontend edit form. It ships there, not in
  `academic_persons_edit`, because the TCA needs it.
- In the frontend edit forms, a `disabled` or `readOnly` property is **never**
  written, whatever the request carries. The shipped `profile` set locks the
  three name fields that way, which is intended and regularly misread.
- Translation synchronisation writes go **through the DataHandler**, never
  through raw queries: nothing on the frontend read path repairs a stale
  `l10n_mode=exclude` value, so the translation rows themselves must be correct.
- TypoScript and page TSconfig exist **once** on disk and are delivered twice:
  a site set points at the very files the static template registration points
  at. Content elements are hidden globally and re-enabled per component.
- Markup that several extensions render lives **once**, as a partial of
  `academic_base`, registered in each plugin view with the root path key `-1`
  — below every key of the extension and of the project, so a project
  override always wins.
- A template a project customises in pieces is **built from pieces**, and the
  entry partial keeps its name and its arguments so an existing copy of it is
  not broken. A partial that renders a *value* rather than markup renders it
  raw: whoever puts it into an element escapes it there, and escaping twice is
  silent until a name carries an apostrophe.
- A content element template renders the frame, the spacing, the `c{uid}`
  anchor and the header **only** when it goes through the `Default` layout of
  whichever package provides `lib.contentElement`. On TYPO3 v14 that header
  partial needs a `record` view variable, which `lib.contentElement` supplies
  itself and an Extbase plugin view does not.
- A **brand** icon — an extension or plugin mark — stays with the core
  `SvgIconProvider` and keeps the colours of its file. A record, category or
  action icon is drawn in `currentColor` and registered with the
  `academic_base` provider that inlines it, so it follows the text colour in
  the backend and the frontend, on the dark cards of a dark colour scheme
  included.
- The category summary of the page module is **one implementation in
  `category_types`** and three four-line listeners on
  `ModifyPageLayoutContentEvent`. It shipped three times as a partial before,
  registered as an override of a core backend partial that no core template
  renders — only `academic_programs` ever showed it, and a rename in March 2023
  ended that.
- A list plugin offers **two** events, not one: a demand event before the query
  and a list event after it, both carrying the plugin context of
  `academic_base`. A restriction the plugin guarantees rather than offers - the
  partner map's "no coordinates, not drawn" - is applied *after* the demand
  event, so a listener cannot take it away. Everything else it can: the demand
  *is* the query, so a listener widens as easily as it narrows.
- A filter or sorting submission of a list plugin answers with a **`303` to a
  GET URL** carrying the selection, built from the demand object rather than
  the request. The sorting is always in it, because only the bare page applies
  the content element's presets - which is also why no route enhancer may
  declare `defaults` for it. The demand is kept out of the cache hash, so
  filter URLs do not multiply page cache entries.
- The profile editor's configuration crosses the Fluid boundary as `data-*`
  attributes on one element, and is **read once** into a frozen object that is
  handed down. No module reads `root.dataset` a second time.
- The filters a list offers are **one setting under one key**,
  `settings.filter.categoryTypes`: site-wide as a constant, per element as a
  FlexForm field that an empty value does not override. It decides what the
  form offers, never what the list accepts, and a type is offered when it has
  any category — not only one on a listed record.
- The facts of a program are built by **one** stateless builder for the three
  places that show them, from a field list per place. An empty list means what
  the place showed before the list existed, and category types keep the order
  of the registry unless the list names them.
- A dead template override is invisible: Fluid resolves the first file it finds
  and says nothing when it finds none of the project's. `academic:upgrade:check`
  reports them, and a root path is a *project* override only when it lies
  outside the checked extension, outside what it depends on and outside the
  system extensions — the partial root paths of every academic plugin name
  another extension's folder.
- The profile editor is five custom elements over Fluid's markup, and **none of
  them renders any**: the two whose content comes out of a response clone
  `<template>` prototypes Fluid emitted. They are plain custom elements with
  no framework below them, no element opens a shadow root and nothing but the
  element itself ever writes below one, so a project's stylesheet reaches every
  control and there is no `::part()` to declare.

## Pages

| Page                                                            | Contents                                                                                                                                                                                   |
|-----------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [Core version aware code](core-version-aware-code.md)           | The version switches that exist today, the one data structure split and what a `Core13/`/`Core14/` PHP split would look like, and the APIs that cannot be migrated while v13 is supported. |
| [Dependency injection](dependency-injection.md)                 | How services are configured across the extensions, why they must be stateless, where a value per request goes instead, and which TYPO3 attributes are safe on both core versions.          |
| [Class design](class-design.md)                                 | `final`, `readonly`, constructor versus method injection, data objects, and the traps in Extbase models.                                                                                   |
| [Database queries](database-queries.md)                         | Quoting value lists, keeping a constraint on the builder that executes it, ordering every result a caller renders or limits, and reusing a list's query to ask about it.                   |
| [Frontend-user contact import](frontend-user-contact-import.md) | How telephone and fax data from `fe_users` is identified, typed, synchronized and migrated.                                                                                                |
| [Validation settings](validation-settings.md)                   | The one YAML that drives both the backend FormEngine and the frontend edit form, its flags, and how an installation overrides it.                                                          |
| [Form data transformation](form-data-transformation.md)         | How a value of a JSON payload reaches the model, why `disabled` wins over everything, and the shipped defaults that surprise people.                                                       |
| [TypoScript and site sets](typoscript-and-site-sets.md)         | The layout that serves site sets and static templates from one physical copy, hide-by-default, and the `clear = 3` trap.                                                                   |
| [Translation synchronization](translation-synchronization.md)   | Why profile translations are written through the DataHandler, the event chain that triggers it, and the contact4pages policy on top of it.                                                 |
| [Shared partials](shared-partials.md)                           | The Fluid partials `academic_base` ships for every extension, the root path key `-1` they are registered with, and which views register it.                                                |
| [Overridable partials](overridable-partials.md)                 | How a template is cut into partials a project overrides one at a time, the escaping contract of a partial that renders a value, and where the classes go.                                  |
| [Page type rendering](page-type-rendering.md)                   | How the page types refine the site's page object: `FLUIDTEMPLATE` against `PAGEVIEW`, the path keys, the layout contract and its fallback.                                                 |
| [Program facts](program-facts.md)                               | The one builder behind the facts of the program page, the details element and the program card, what an empty field list means, and the partial per row.                                   |
| [List plugin events](list-plugin-events.md)                     | The demand and list events of the partner and project lists, the plugin context they carry, and the rules that are easy to get wrong.                                                      |
| [List filter URLs](list-filter-urls.md)                         | The redirect of a filter submission, the demand in its URL, what is carried and why, the links of a pagination and of the profile list, and why an enhancer declares no defaults.          |
| [List filter types](list-filter-types.md)                       | Which category filters a list offers and in which order, the site and element level of one setting, the rule for a type without categories, the field items, and the program finder.       |
| [Icons](icons.md)                                               | Where icons are registered and consumed, the two markups, when to use the `currentColor` provider, and keeping a template's icons resolvable.                                              |
| [Page module category summary](page-module-category-summary.md) | The listener and the shared renderer behind the category table of the page module, the override key, and why the labels come from the registry.                                            |
| [The profile editing contract](profile-editing-contract.md)     | The `data-*` attributes the profile editor is configured with, the reader that parses them once, and the five custom elements that drive it.                                               |
| [Backend select items](backend-select-items.md)                 | What an `itemsProcFunc` handler is handed on each core version, the page TSconfig path of a FlexForm field, and the narrowing that silently drops a relation.                              |
| [Content element rendering](content-element-rendering.md)       | The two rendering shapes, what the `Default` layout renders, who renders a plugin's header, and the `record` variable TYPO3 v14 needs for it.                                              |
| [Upgrade checks](upgrade-checks.md)                             | The `academic:upgrade:check` command: the stored configuration it reads, what it compares template overrides with, and which root paths are a project's.                                   |

## See also

- [Dual core setup](../development/dual-core-setup.md) — running against both
  core versions.
- [Quality gates](../development/quality-gates.md) — PHPStan is configured per
  core version.
- [Testing](../testing/Index.md) — how core version differences are covered by
  tests.
