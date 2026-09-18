# The page module category summary

A program, project or partner page carries its categories in the page
properties, where an editor has to open a form to see them. The page module
shows them instead, as a table above the content grid: one row per registered
category type of the page's group, with the categories assigned to that page.

This page is about how that summary is wired. How an own extension asks for it,
how an installation replaces the markup and what the template is given are
documented in the `For Developers` chapter of `category_types`, section
`The page module category summary`.

## It is a listener, not a template override

The summary is appended by a listener on
`TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent`, which
`PageLayoutController` dispatches and whose header content the page module
template renders as `eventContentHtmlTop`. The event exists unchanged on TYPO3
v13 and v14, and so does that template variable.

Overriding the page module template itself was the other candidate, and one
analysed project had done exactly that. It was rejected: the copy has to be
maintained against every core change to that template, and two extensions doing
it collide — only one copy can win.

Each of the three extensions registers its own listener with TYPO3's
`#[AsEventListener]`, never Symfony's, and passes two constants: the page type
it owns and its category group.

| Extension           | Page type | Category group |
|---------------------|-----------|----------------|
| `academic_programs` | 20        | `programs`     |
| `academic_projects` | 30        | `projects`     |
| `academic_partners` | 40        | `partners`     |

A registry mapping page types to groups in `category_types` was rejected as
well: it would be a registration API for three constants that never change.

## Everything else lives in `category_types`

`FGTCLB\CategoryTypes\Backend\PageCategorySummaryRenderer` holds the whole
implementation — resolving the page from the request, the permission check, the
doktype comparison, the query and the view — and the three listeners are four
lines each.

That split is deliberate. The summary shipped three times before, as
`Resources/Private/Backend/Partials/PageLayout/Doktype{20,30,40}.html` of the
three extensions, and so did the two defects those partials carried.

### What actually happened — and it differs per extension

Two review rounds were spent on this sentence, because it was written once and
copied three times. It does not generalise, so here it is per extension.

**`academic_programs` used to work.** `861d7d0e9` (2023-03-16) added the markup
as `Resources/Private/Backend/Templates/PageLayout/PageLayout.html`, registered
as `module.tx_backend.view.templateRootPaths.10` — the TYPO3 v10/v11 way of
overriding core's page module template (Feature-90348). It rendered the category
callout and then delegated to core's own `PageLayout/Grid` partial. `7b8eac8cf`,
*[TASK] Add dynamic page layout rendering* (2023-03-17), renamed it to
`Partials/PageLayout/Doktype20.html` and changed `templateRootPaths` to
`partialRootPaths`. **That is the commit that broke it.**

**`academic_projects` and `academic_partners` never worked.** Their partials were
added *as partials*, with a `partialRootPaths` registration, in `784742607`
(2023-09-19) and `bb4c01300` (2025-02-25) — the already-broken programs shape,
copied six months and two years after the rename. Across every ref, exactly one
`Backend/Templates/PageLayout/*` file was ever added to this repository, and it
is the `academic_programs` one.

That also explains a leftover. `academic_projects` was the only extension still
carrying an `ext_typoscript_setup.typoscript` with `partialRootPaths`, because it
was born with one, and the 2025 cleanup that removed that file from the other two
skipped it.

Nothing in `typo3/cms-backend` renders a `PageLayout/Doktype*` partial. Measured
on 12.4.45, 13.4.34, 13.4.35, 14.3.6 and 14.3.7 — every version either
maintained branch supports. The v10/v11 trees the rename was written against are
not installed here and were not checked; the name was invented by this
repository, so it is unlikely core ever rendered it, but that is an argument and
not a measurement.

TYPO3 v12.0 then removed the `module.tx_backend.view` mechanism entirely
(Breaking-96812) in favour of the TSconfig key `templates.<package>.<key>`, and
the `templates.typo3/cms-backend.academic-<ext>` line each extension carries
today is that replacement — still pointing at the partial nothing renders.

So a statement about "the three partials" has to hold for all three: none of
them renders today, and one of them used to.

### The second defect

The partials translated `sys_category.academic_<ext>.{type}`, a key that exists
in no XLF file of any of the three. The real keys are `sys_category.<group>.*`,
and every type label would have been empty even once something rendered them.

One copy of a thing has one defect, not three.

## The package name is load-bearing

The renderer creates its view with

```php
$this->backendViewFactory->create($request, ['fgtclb/category-types']);
```

and that second argument does two things at once. It puts this extension's
`Resources/Private/Templates/` on the search path — without it the factory
falls back to the package of the request's `route` attribute, which in the page
module is `typo3/cms-backend`, and the template is not found at all. And it is
what makes `BackendViewFactory::create()` read

```
templates.fgtclb/category-types.<key> = <composer-package>:<path>
```

from the page TSconfig, which is the override seam an installation uses. The
override directory is searched as `<path>/Templates/PageCategorySummary.html`.

`PageCategorySummaryTemplateOverrideTest` covers it with a fixture extension
that registers such an override in its own `Configuration/page.tsconfig`.

## Labels come from the registry, not from a key convention

Each row's label is the `title` the category type was registered with in
`Configuration/CategoryTypes.yaml`, resolved with `LanguageService::sL()`.

`sL()` and not `f:translate`: a title does not have to be an `LLL:` reference.
`CategoryTypes.yaml` takes a literal string just as well, and a project that
adds a type of its own does write one. `sL()` resolves a reference and returns
anything else unchanged, where `f:translate` answers an empty string for the
literal — the same empty label the old key convention produced.

Fixing the key to `sys_category.<group>.{type}` would have repaired the shipped
types and left a project's own type unlabelled, which is why the registry is
asked instead.

## What the summary shows

`CategoryCollection::getAllCategoriesByType()` answers with **every** registered
type of the group, including the ones the page carries no category of, and
those rows render a "Not set" note. A summary listing only the assigned types
would leave an editor guessing whether a type exists at all.

Hidden categories are asked for on purpose and are listed with the core
`overlay-hidden` icon in place of the type icon. A category that is assigned and
switched off is the one thing the page properties would not show either.

The renderer answers with an empty string — never an exception, never a flash
message — for a page of another type, a page the backend user may not see, and a
category group no active extension registers. `addHeaderContent('')` appends
nothing, so the page module of a standard page is unchanged.

A request that addresses no page at all returns early too, but that is a
short-circuit rather than behaviour: with no page there is no record, the
doktype comparison rejects it anyway, and neither core version dispatches the
event for page 0 in the first place. `aRequestWithoutAPageGetsNoSummary()` is
therefore a guard — it stays green when the early return is removed, which was
measured, not assumed.

## See also

- [Dependency injection](dependency-injection.md) — why the listeners use
  TYPO3's `#[AsEventListener]` and not Symfony's.
- [Icons](icons.md) — how the category type icons are registered and which
  provider inlines them.
- [Core version aware code](core-version-aware-code.md) — the version
  differences that do and do not need a switch.
- [Functional tests](../testing/functional-tests.md) — the harness the
  renderer and listener tests run in.
