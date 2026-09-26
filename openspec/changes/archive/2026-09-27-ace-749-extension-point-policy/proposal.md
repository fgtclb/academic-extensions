## Why

Projects extend the academic extensions by subclassing controllers or
XCLASSing classes, because nothing tells them what they may rely on. One
project subclasses a controller to add pagination variables; another replaces
the final profile controller with a copy and XCLASSes the profile repository
to add one condition. Each breaks on releases that do not mention the class.
`docs/architecture/class-design.md` makes classes `final` by default, but no
page tells an integrator what the public API is. ACE-445 shows the other side
of the gap: an extension point that is documented and never dispatched.

## What Changes

- An integrator chapter in `academic_base`,
  `Documentation/Developers/ExtensionPoints/`, listing the public API of all
  academic extensions and `category_types`: the events with their dispatch
  location, the types they hand over, the interfaces, the services and
  classes a project names in configuration or code, two controller traits,
  the domain models, and the non-PHP surface (templates, settings, TypoScript
  and TSconfig keys, label keys, `CategoryTypes.yaml`).
- The list is a whitelist: what it does not name is not API, `final` or not.
  Subclassing or XCLASSing is unsupported except for a domain model. The five
  controllers that are not `final` yet are named.
- Every listed class, interface, trait and enum carries `@api`.
- The minimum set of extension points: a view event per plugin action, a
  demand event per repository query, an after-save event per write, marked as
  existing or planned.
- A contributor rule in `docs/architecture/class-design.md` for events and
  listed API.
- A repository test: every event class is `final` and created by other
  production code, every domain model carries `@api`, no class is both `@api`
  and `@internal`, and the page and the tags name the same classes.
- `academic:upgrade:check` reports an XCLASS of a domain model as a notice
  instead of a warning, since that is the supported way to add fields to a
  model; every other XCLASS is reported as before.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-base/upgrade-configuration-check`: an XCLASS of a domain model is
  reported as a notice.

## Impact

- `academic-base/Documentation/` gains a `Developers/` section, linked from
  the other manuals.
- `@api` tags; no other code change apart from `ConfigurationChecker` and its
  functional test.
- `docs/architecture/class-design.md` and the pages describing the tests and
  the upgrade check.
- A unit test in `packages-dev/monorepo-shared`, never shipped.
- An `Important-` changelog entry in `academic_base`.
- Lands after `ace-442-single-action-context-interface` (merged) and before
  `ace-tbd-generic-plugin-view-event`.

## Non-goals

- Making the five open controllers `final`, or dropping `final` elsewhere.
- Adding the events themselves; each arrives with its own change.
- Renaming events that do not follow the naming rule.
- Tagging unlisted classes `@internal`.
- A backport to branch `2`.

## Source

Project differences analysis of 2026-09-12, candidate `cross-cutting-11`;
four of six projects carry their own code for this. Filed as ACE-749.

Relates to ACE-445.
Relates to ACE-507.
