## Why

The plugin action context every academic plugin event carries promises to
answer `null` when its request lacks something, and three of its getters do
not keep that promise. The site and the language getters fail with a type
error when the request carries core's `NullSite` or any other value under
those names, and the application type getter throws core's exception
1606222812 for a request without an application type, which the interface
does not mention. Since ACE-767 one context reaches every listener of every
academic plugin event, and the interface is `@api` from 3.0 on, so this has
to be settled before 3.0.0 is tagged (ACE-748).

## What Changes

- The site and the language getters of the context of `academic_base`
  (`packages/fgtclb/academic-base`) and of the deprecated copy in
  `academic_persons` (`packages/fgtclb/academic-persons`) answer `null` for
  a request value that is not a site or a site language, instead of failing
  with a type error. No declared type changes.
- The application type getter, recommended option: keeps its declared type
  and keeps throwing for a request no TYPO3 application handled. The
  interface states the exception, and the extension points page of
  `academic_base` says so next to the context.
- Alternative, decision for the maintainer: the application type getter
  answers `null` as well. **BREAKING** for every listener that uses the value
  without a check and for every own implementation of the interface. It has
  to land before 3.0.0, otherwise it moves to 4.0 with ACE-747.

The behaviour is the same on TYPO3 v13 and v14: the core classes and the
exception involved are identical on 13.4.35 and 14.3.7.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-base/plugin-action-context`: reading the site and the language
  never fails, and what reading the application type does for a request no
  TYPO3 application handled.
- `academic-persons/plugin-action-context`: the same for the context of the
  persons plugins.

## Impact

- The context class of `academic_base`, its interface docblock, and the
  deprecated context class of `academic_persons`. The persons interface
  extends the base one and changes with it.
- The page title provider of `academic_persons` reads the language from the
  context and falls back to the backend user's language for a foreign value
  instead of failing.
- An `Important-` changelog entry in `academic_base` (recommended option), or
  a `Breaking-` entry (alternative). The extension points page of
  `academic_base`, `docs/architecture/list-plugin-events.md`.

## Non-goals

- Removing the persons context or retyping the persons events. That is 4.0
  and ACE-747.
- Catching core's exception inside the getter while keeping the declared
  type. There is no application type to return for such a request.
- A backport to branch `2` as part of this change. The two type checks are a
  compatible bugfix there and may follow as a change of its own on `2`. The
  application type decision cannot, a changed return type is not allowed in
  the 2.x line.

## Source

Board triage of 2026-10-12, report `ACE-748`. Found in ACE-442 and left out
of it on purpose. Related: ACE-442, ACE-747, ACE-767.
