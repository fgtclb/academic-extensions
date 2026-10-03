## Context

See `proposal.md` for the motivation. On `main`:

- Every frontend label of a category type is
  `f:translate(key: 'sys_category.<group>.<identifier>', extensionName: …)`:
  - programs: `Partials/Program/Facts/Item.html` (through the `labelKey` of a
    category type fact), `Partials/Program/DemandCategories.html` and
    `Templates/Program/Finder.html`;
  - partners: `Partials/Partner/Page/Categories.html`,
    `Partials/Partner/Item.html`, `Partials/Partnerships/List/Item.html`,
    `Partials/Partnerships/Teaser/Item.html` and
    `Partials/Partner/DemandCategories.html`;
  - projects: `Partials/Project/Page/Categories.html`,
    `Partials/Project/Item.html` and `Partials/Project/DemandCategories.html`.
- `f:translate` already reads the `_LOCAL_LANG` overrides of the extension and
  of the plugin (the `label-overrides` capability of each extension), and
  returns its `default` argument when no label exists.
- The registry of `category_types` knows each type's `title`, an `LLL:`
  reference or a literal. The shipped titles point to `locallang_be.xlf`. The
  page module summary resolves them with `LanguageService::sL()`, because
  `f:translate` answers an empty string for a literal
  (`docs/architecture/page-module-category-summary.md`).
- The programs manual documents the gap in the facts and the filter chapters
  ("such a type needs that label added … or its row shows no label").

## Goals / Non-Goals

**Goals:**

- One way to name a type in the frontend, shared by the three extensions.
- The existing override paths keep working unchanged.

**Non-Goals:**

- Changing the label keys or the "all" option keys.
- Moving the shipped labels from `locallang.xlf` to the registry.

## Decisions

### The registered title is the `default` of the existing lookup

Each site keeps its `f:translate` call and gains
`default: '{ct:categoryTypeTitle(group: …, identifier: …)}'`. A new ViewHelper
of `category_types` returns the registered title of the type, resolved with
`sL()` of a language service for the site language of the request (the
rendering context's request attribute, as `persons:contracts` reads it on both
core versions). It returns an empty string for an unknown type.

`f:translate` evaluates `default` only when no label exists, so the
precedence of the spec (plugin label, extension label, registered title)
follows from the call itself and the `_LOCAL_LANG` handling is not touched.

Rejected: a ViewHelper that replaces `f:translate` and does both lookups. It
would have to repeat the plugin path handling of `_LOCAL_LANG` that the core
does differently on v13 and v14. Rejected: a `title` on the program fact
only. It covers one of eleven sites and leaves partners and projects as they
are.

### The ViewHelper lives in `category_types`

All three extensions depend on `category_types`, which owns the registry and
already ships the `ct` namespace (`xmlns:ct`) that the filter partials of the
three extensions declare. It is listed on the
extension points page of `academic_base` as a template API, by the policy of
`ace-749-extension-point-policy`.

### A shipped type keeps the label of its extension

The shipped types all have a `sys_category.<group>.<identifier>` label, so the
fallback never reaches them. Their registered titles stay backend labels.

## Risks / Trade-offs

- [A project override of one of the eleven templates and partials keeps the
  old lookup] → The `Feature` entries name the `default` argument to add.
- [A literal title is not translated] → It is shown as written, which is what
  the backend shows too. A project that needs translations uses an `LLL:`
  reference.
