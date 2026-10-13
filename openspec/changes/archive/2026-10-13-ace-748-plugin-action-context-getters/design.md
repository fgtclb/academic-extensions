## Context

Read on `origin/2` `efe3f6b45`. Measured against TYPO3 12.4 and 13.4 in the
worktree's vendor tree after `composerUpdate` for each.

- `academic-base/Classes/Domain/Model/Dto/PluginControllerActionContext.php`
  (`final`) returns the request attributes `site` (`getSite(): ?Site`) and
  `language` (`getLanguage(): ?SiteLanguage`) unchecked.
  `getExtbaseRequestParameters()` already checks with `instanceof`. Unlike
  `main`, `getContentObjectRenderer()` is unchecked too, because ACE-442 is a
  3.0 change.
- `academic-persons/Classes/Domain/Model/Dto/PluginControllerActionContext.php`
  (`final`) has the same two getters. On this branch it is not deprecated,
  has no content object getter, and implements its own interface, which does
  not extend the one of `academic_base`. Five persons events and the page
  title provider hand it over.
- `NullSite` implements `SiteInterface` and is no `Site`, and
  `ApplicationType::fromRequest()` throws `\RuntimeException` 1606222812
  without the attribute and `\LogicException` 1678875015 for a type that is
  neither frontend nor backend, on both cores.
- `ProfileTitleProvider` reads `getLanguage()` and falls back to the backend
  user's language when it is `null`, and to English without a logged-in
  backend user.

## Goals / Non-Goals

**Goals:**

- The site and the language getters of both contexts answer `null` instead
  of failing, with no declared type changed.
- Both interfaces say that the application type getter raises.

**Non-Goals:**

- The application type and the content object getter, see the proposal.

## Decisions

### An instanceof check in each getter, as on `main`

`getSite()` returns the attribute only when it is a `Site`, `getLanguage()`
only when it is a `SiteLanguage`, in both classes. It is the shape the
extbase parameters getter has on this branch already.

Rejected: returning a `NullSite` as `SiteInterface`. It widens the declared
type, which a 2.x release must not do.

### The application type is only documented

Stefan decided option A for `main` on 2026-10-13: keep the type and document
the exception. On this branch only the docblock travels, on both interfaces,
since they are separate here.

### One changelog entry per extension

Unlike `main`, the persons context is its own API on this branch and not
deprecated, so each extension gets an `Important-` entry in
`Changelog/2.4/`.

### No docs page changes

No page under `docs/` and no manual page of either extension describes the
context on this branch. The changelog entries are the documentation.

## Risks / Trade-offs

- [A listener relies on the `TypeError` to detect a wrong request] → none
  known, and a declared nullable return makes `null` the documented answer.

## Migration Plan

Nothing to do. A listener that caught the `TypeError` gets `null` instead.

## Open Questions

None.
