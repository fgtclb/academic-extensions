## Why

The plugin action context promises to answer with none when its request
lacks the site or the language, and does not keep that promise. The site and
the language getters fail with a type error when the request carries core's
`NullSite` or any other value under those names. The backend stores a
`NullSite` for page 0 and for a page outside any site. `main` fixed this in
the change of the same name (ACE-748), and the two type checks are a
compatible bugfix for the 2.x line.

## What Changes

- The site and the language getters of the context of `academic_base`
  (`packages/fgtclb/academic-base`) and of the context of `academic_persons`
  (`packages/fgtclb/academic-persons`) answer with none for a request value
  that is not a site or a site language, instead of failing with a type
  error. No declared type changes.
- Both context interfaces state that reading the application type raises
  core's exception for a request without one. The getter itself is unchanged.

The behaviour is the same on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `academic-base/plugin-action-context`: reading the site and the language
  of the plugin action context never fails.
- `academic-persons/plugin-action-context`: the same for the context of the
  persons plugins.

### Modified Capabilities

None.

## Impact

- The context class and interface of `academic_base`, and the context class
  and interface of `academic_persons`, which are separate on this branch.
- The page title provider of `academic_persons` reads the language from the
  context and falls back to the backend user's language for a foreign value
  instead of failing.
- One `Important-` changelog entry in `Changelog/2.4/` of each extension.

## Non-goals

- The application type. A changed return type is not allowed in the 2.x
  line, and `main` keeps the type too (option A of ACE-748).
- The content object getter of the `academic_base` context, which `main`
  checks since ACE-442 (a 3.0 change). Left as it is on this branch.
- Anything of ACE-442 or ACE-767, such as one interface for both contexts.

## Source

Backport of `ace-748-plugin-action-context-getters` on `main`. Analysis:
the backport section of the ACE-748 prompt. Related: ACE-442.
