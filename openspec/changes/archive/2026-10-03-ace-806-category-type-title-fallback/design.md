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
  returns an empty string when no label exists.
- The registry of `category_types` knows each type's `title`, an `LLL:`
  reference or a literal. The shipped titles of programs point to
  `locallang_be.xlf`, those of partners and projects to `locallang.xlf`. The
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

### The registered title follows the existing lookup

Each site keeps its `f:translate` call and hands its result to a new ViewHelper
of `category_types`:
`{f:translate(…) -> ct:categoryTypeTitle(group: …, identifier: …)}`. The
ViewHelper renders its content when it is not empty, and otherwise the
registered title of the type, resolved with `sL()` of a language service for
the site language of the request (the rendering context's request attribute,
as `persons:contracts` reads it on both core versions). The title of an
unknown type is empty. Without a site language, as in a command, it resolves
the title in the default language.

The precedence of the spec (plugin label, extension label, registered title)
follows from the order of the two calls, and the `_LOCAL_LANG` handling is not
touched. An empty label counts as none, so a site that blanks a label gets the
title. A label nobody sets is empty as well, and the two cannot be told apart
after `f:translate`. The shipped partner and project types on TYPO3 v13 are the
exception: their title is the label reference itself, which `sL()` answers
from its cache with the blanked label, so the type stays unlabelled there. The
partner tests pin both behaviours, so a core patch that changes the cache shows
up.

Changed during the implementation: the first design passed the title as the
`default` of `f:translate`. Fluid evaluates that argument before the
ViewHelper runs, and `sL()` of TYPO3 v13 caches a resolved label for the whole
request by locale and reference. The shipped titles of partners and projects
are the reference `f:translate` reads, so the title, resolved first, was
handed to `f:translate` in place of the site's `_LOCAL_LANG` label. The label
override tests of both extensions failed on v13. Programs was not affected
only because its titles point to `locallang_be.xlf`. Resolving the title after
the lookup keeps any title out of the way of a label.

Rejected: a ViewHelper that replaces `f:translate` and does both lookups. It
would have to repeat the plugin path handling of `_LOCAL_LANG` that the core
does differently on v13 and v14. Rejected: pointing the shipped titles of
partners and projects to `locallang_be.xlf`. It removes the collision for the
shipped types and leaves it for a project that retitles one of them with the
reference of the extension. Rejected: a `title` on the program fact only. It
covers one of eleven sites and leaves partners and projects as they are.

### The ViewHelper lives in `category_types`

All three extensions depend on `category_types`, which owns the registry and
already ships the `ct` namespace (`xmlns:ct`) that the filter partials of the
three extensions declare. It is listed on the
extension points page of `academic_base` as a template API, by the policy of
`ace-749-extension-point-policy`.

### A shipped type keeps the label of its extension

The shipped types all have a `sys_category.<group>.<identifier>` label, so the
fallback never reaches them. Their registered titles stay backend labels. Their
labels and titles read the same today, so the tests retitle one shipped type
per extension in their fixture to see that the label wins.

### The facts item hands every fact label to the ViewHelper

A built-in fact (`creditPoints`, `jobProfile`, `performanceScope`,
`prerequisites`) is not a registered type, so its title is empty, and its label
`program.<field>` always exists. A condition for category type facts only
would change nothing.

## Risks / Trade-offs

- [A project override of one of the eleven templates and partials keeps the
  old lookup] → The `Feature` entries show the call to hand the label to.
- [A literal title is not translated] → It is shown as written, which is what
  the backend shows too. A project that needs translations uses an `LLL:`
  reference.
