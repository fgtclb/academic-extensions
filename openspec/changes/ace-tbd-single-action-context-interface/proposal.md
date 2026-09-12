## Why

The plugin action context exists twice: in `academic_base` and in
`academic_persons`. The two interfaces declare the same methods, except that
the persons copy has no access to the content element. The persons events
type against their own copy, so a listener written for a jobs or base event
cannot be reused for a persons event, and the generic plugin view event of
`ace-tbd-generic-plugin-view-event` needs one type both can share.

## What Changes

- The persons `PluginControllerActionContextInterface` of `academic_persons`
  (`packages/fgtclb/academic-persons`) extends the one of `academic_base`
  (`packages/fgtclb/academic-base`) and is marked deprecated, for removal in
  4.0.
- The persons context class provides the content element, resolved the same
  way as the `academic_base` context does it.
- The persons events keep their declared types, so listeners typed against the
  persons interface keep working, and listeners typed against the
  `academic_base` interface work as well.
- **BREAKING** Third-party classes implementing the persons interface must add
  the content element accessor.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/plugin-action-context`: what a listener of a persons
  plugin event can rely on from the context it receives.

### Modified Capabilities

None.

## Impact

- Two files in `academic_persons` (interface and context class); no change in
  `academic_base`.
- The five persons events typed against the persons interface are unchanged
  in signature.
- `Breaking-` and `Deprecation-` changelog entries in `academic_persons`.

## Non-goals

- Deleting the persons interface in 3.0; it would be a fatal error for every
  listener that type-hints it.
- Changing the declared types of the persons events; that happens with the
  removal in 4.0.
- A backport to branch `2`: it changes API.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-13`). Four of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-single-action-context-interface` when the issue is filed after
implementation.
