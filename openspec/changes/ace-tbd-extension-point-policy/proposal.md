## Why

Projects extend the academic extensions by subclassing controllers or by
XCLASSing classes, because nothing tells them what they may rely on. One
project subclasses a controller to add pagination variables, which breaks on
every constructor or action change. `docs/architecture/class-design.md` makes
classes `final` by default, but that is a rule for contributors; no page tells
an integrator what the public API is. ACE-445 shows the other side of the same
gap: an extension point that is documented and never dispatched.

## What Changes

- An integrator page in `academic_base` (`packages/fgtclb/academic-base`),
  `Documentation/Developers/ExtensionPoints/`, stating the public API of all
  academic extensions and `category_types`: PSR-14 events, interfaces, traits
  marked `@api`, templates, partials and sections, settings, TypoScript and
  TSconfig keys, label keys, and `CategoryTypes.yaml`.
- Every class, interface and trait the page lists carries an `@api`
  docblock tag, so the marker sits next to the code; the page is the list
  integrators read.
- The same page states what is not API: `final` and `@internal` classes. An
  XCLASS of them is unsupported.
- It names the minimum set of extension points: a view event per plugin
  action (`ace-tbd-generic-plugin-view-event`), a demand event per repository
  query and an after-save event per persistence write, the latter two added by
  the changes of the individual extension families.
- A contributor rule in `docs/architecture/class-design.md`: events are
  `final`, named `Modify…Event` or `After…Event`, expose setters only for what
  may change, carry the plugin action context of `academic_base` when a
  plugin action dispatches them, carry `@api` when the page lists them, and
  every documented extension point is dispatched and tested.
- A repository-level architecture test: every event class of every extension
  is `final` and instantiated by production code at least once.

Nothing changes at runtime, on TYPO3 v13 or v14.

## Capabilities

### New Capabilities

None. The change is documentation plus a repository test and sets
`skip_specs: true`.

### Modified Capabilities

None.

## Impact

- `academic-base/Documentation/` gains a `Developers/` section; the
  developer pages of the other extensions link it.
- `docs/architecture/class-design.md` gains an extension point section.
- An `@api` docblock tag on each class, interface and trait the page lists
  that does not carry one yet; no code changes otherwise.
- Lands after `ace-tbd-single-action-context-interface` and before
  `ace-tbd-generic-plugin-view-event`.
- A unit test below `packages-dev/`, never shipped with an extension.
- An `Important-` changelog entry in `academic_base`.

## Non-goals

- Dropping `final` from controllers or services.
- Adding the events themselves; each arrives with its own change.
- Renaming existing events that do not follow the naming rule.
- Annotating classes the page does not list with `@api` or `@internal`.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-11`). Four of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-extension-point-policy` when the issue is filed after implementation.

Relates to ACE-445.
Relates to ACE-507.
