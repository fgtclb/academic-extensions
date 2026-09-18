## Context

Backport of ACE-687 (renderer), ACE-688 (programs), ACE-689 (projects) and
ACE-690 (partners). Measured on this branch at `9abac9c94`, against `main` at
`57f3e92cc`; the v12 tree is TYPO3 **v12.4.45**, Fluid **2.15.0**, and the v13
tree is **13.4.35**.

What is the same:

- The three partials `Doktype{20,30,40}.html` are **byte-identical** to the ones
  on `main`, and all three `Configuration/page.tsconfig` register them at line
  17 with the same key. The `page.tsconfig` files differ from `main` only by
  three trailing comment lines about v12 having no site set layer.
- `ModifyPageLayoutContentEvent` exists on v12.4.45 with the same method set,
  is dispatched by `PageLayoutController:132`, and its header content is
  rendered as `eventContentHtmlTop` by `PageModule.html:50`.
- `BackendViewFactory::create($request, $packageNames)` resolves
  `templates.<package>.<key>` from page TSconfig at **line 94** — the same line
  as on v13 and v14.
- `BackendUtility::readPageAccess()` exists (v12 `:600`, no return type, like
  v13), and `LanguageServiceFactory::createFromUserPreferences()` exists on both
  (`createForBackendUser()` is v14 only and is not used).
- `CategoryCollection` is byte-identical to `main`, `getAllCategoriesByType()`
  included — the method the "not set" row depends on.
- The fixture extension `test_category_types_group` exists here with the same
  `testing` group and the same two types, so the renderer tests need no new
  fixture extension.

## Goals / Non-Goals

**Goals:** the same behaviour as on `main`, on TYPO3 v12 and v13, with one
implementation in `category_types` and three listeners.

**Non-Goals:** the ordering defect below, and anything the `main` change
declared out of scope.

## Decisions

### The listeners are registered with the `event.listener` tag

`TYPO3\CMS\Core\Attribute\AsEventListener` **does not exist on TYPO3 v12** —
`Core\Attribute\` holds only `AsAllowedCallable` and `WebhookMessage` there. The
three listeners are therefore registered in each extension's
`Configuration/Services.yaml` with the `event.listener` tag, which is what the
existing listeners of this branch do.

Symfony's attribute of the same name is **not** the fallback: it registers
nothing in TYPO3 and the listener silently never fires.

### `final class`, not `final readonly class`

PHP 8.1 has readonly *properties* but not readonly *classes*, and this branch
floors at 8.1. The renderer and the three listeners are `final class` with
promoted `private readonly` constructor properties. No class on this branch is a
readonly class.

### Everything else is the `main` implementation

`PageCategorySummaryRenderer` resolves the page from the request itself, guards
on the doktype, reads the categories through `CategoryRepository` and renders
`PageCategorySummary.html` through `BackendViewFactory` under the package name
`fgtclb/category-types`. The reasoning is on `main` and is not repeated here.

## Risks / Trade-offs

- [**No `ORDER BY` in `findByGroupAndPageId()` on this branch**] → ACE-482 and
  ACE-491 added the `sorting` + `uid` ordering on `main` only; the method here
  ends in `)->executeQuery()`. The order of the categories inside a type row is
  therefore whatever the DBMS returns, and **no test of this change may assert
  one**. The tests assert presence and counts, which is what makes them portable
  unchanged. Fixing the ordering is ACE-431.
- [`CategoryType` has no `inlineIcon`] → an ACE-523 addition on `main`. The
  renderer uses only `getTitle()` and `getIconIdentifier()`, both present.
- [The header slot is shared with other extensions' listeners] → the summary is
  appended, never set.
- [Three listeners read the page record on every page module render] → as on
  `main`; accepted for the same reason.

## Migration Plan

Nothing to migrate. A project that overrode `PageLayout/Doktype*.html` moves the
override to the new TSconfig key.

## Open Questions

None.
