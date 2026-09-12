## Context

- `academic-base/Classes/Domain/Model/Dto/PluginControllerActionContextInterface.php`
  and `academic-persons/Classes/Domain/Model/Dto/PluginControllerActionContextInterface.php`
  declare the same twelve methods; only the base one adds
  `getContentObjectRenderer(): ?ContentObjectRenderer`.
- The base context class resolves it from the request attribute
  `currentContentObject`; the persons context class
  (`academic-persons/Classes/Domain/Model/Dto/PluginControllerActionContext.php`,
  `final`) has no such method.
- Five persons events type against the persons interface:
  `ModifyListProfilesEvent`, `ModifyDetailProfileEvent`,
  `ModifySelectedProfilesEvent`, `ModifySelectedContractsEvent` and
  `ModifyProfileTitlePlaceholderReplacementEvent`. The analysis counted six;
  the source has five.
- The persons context is built by `ProfileController` and by
  `ProfileTitleProvider`, the latter outside any content element.
- `academic_jobs` and `academic_persons_edit` already use the base context.

## Goals / Non-Goals

**Goals:**

- One interface that every academic plugin event context satisfies.
- No listener breaks in 3.x.

**Non-Goals:**

- Removing the persons interface or its class; that is 4.0.

## Decisions

### The persons interface extends the base interface

`interface PluginControllerActionContextInterface extends
\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
with an empty body and a `@deprecated` docblock naming 4.0. The persons class
gains `getContentObjectRenderer()` with the same request-attribute lookup as
the base class.

Rejected: deleting the persons copy in 3.0, a fatal error for every listener
that type-hints it. Rejected: a class alias from the persons name to the base
interface. It needs the class alias loader wiring in the extension's
`composer.json`, and it makes the deprecated name indistinguishable from the
real one for static analysis and IDEs; `extends` gives the same compatibility
in plain PHP.

### The persons events keep their declared types

Widening their getter return types to the base interface now would break a
listener that hands the result on to code typed against the persons
interface. The types change with the removal in 4.0.

### No runtime deprecation notice

An interface cannot raise one. A notice from the persons context constructor
would fire on every persons request and, with `failOnDeprecation`, turn every
persons functional test red for a deprecation nobody can act on yet. The
deprecation is a docblock tag and a `Deprecation-` changelog entry.

### Decided: lands first, before the policy and the view event

This change lands before `ace-tbd-extension-point-policy` and
`ace-tbd-generic-plugin-view-event`. It is small and does not break
listeners, the view event already depends on it, and landing it first lets
the policy name the academic_base context as the one context type without a
transitional paragraph. Its documentation therefore does not touch the
extension point section of `docs/architecture/class-design.md`, which the
policy change writes afterwards.

### Decided: removal and retyping in 4.0

The persons interface and the persons context class are deprecated in 3.0 and
removed in 4.0, together with switching the declared types of the remaining
persons events to the `academic_base` interface. Removing the interface or
widening the event getters within 3.x is a fatal error or a type break for
listeners, so only a major release can do it. Of the five persons events
typed against the persons interface today, `ace-tbd-generic-plugin-view-event`
removes the list, detail, selected profiles and selected contracts events in
3.0 as a breaking change; the 4.0 retyping then concerns the profile title
placeholder event.

### Keep the persons class

The persons controller and the page title provider keep building the persons
context, because the persons events require its type. It is marked
deprecated together with the interface.

## Risks / Trade-offs

- [A project implements the persons interface itself] → the `Breaking-`
  changelog entry names the one method to add; no such implementation was
  found in the analysed projects.

## Migration Plan

- Listeners: nothing to do; typing against the base interface is recommended.
- Implementers of the persons interface: add `getContentObjectRenderer()`.

## Open Questions

None.
