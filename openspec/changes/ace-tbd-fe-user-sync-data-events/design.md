## Context

- `ChooseProfileFactoryEvent` swaps the whole factory
  (`academic-persons/Classes/Event/ChooseProfileFactoryEvent.php`). It is
  dispatched by `ProfileCreateCommandService.php:153` and
  `ProfileUpdateCommandService.php:157`.
- `AbstractProfileFactory::createProfileFromFrontendUser()` must return a
  `Profile` (`Classes/Profile/AbstractProfileFactory.php:187`).
  `createProfileForUser()` is already `?int` in `ProfileFactoryInterface`,
  and `ProfileCreateCommandService.php:95` ignores its result.
- `ProfileActionType` (`Create`, `Update`) already exists.
- Factories are shared services (`ProfileFactory.php:25`), so a cache inside
  a project factory is state that crosses frontend users.

## Goals / Non-Goals

**Goals:**

- Move project logic from factory subclasses into stateless listeners that
  keep every upstream fix to matching and hidden handling.

**Non-Goals:**

- Letting a listener choose a different factory. That is what
  `ChooseProfileFactoryEvent` does.

## Decisions

### Two events, dispatched by the abstract factory

- `final class ModifyFrontendUserSyncDataEvent` has
  `getFrontendUserData(): array`, `setFrontendUserData(array)`,
  `getAction(): ProfileActionType`, `getProfile(): ?Profile` (null on
  create), `skip()` and `isSkipped()`. It is dispatched before mapping, in
  `createProfileForUser()` and per profile in `updateProfileForUser()`.
- `final class AfterProfileMappedFromFrontendUserEvent` has `getProfile()`,
  `getFrontendUserData()` and `getAction()`. It is dispatched after mapping,
  before `persistAll()`.

Both are dispatched in `AbstractProfileFactory`, so every subclass gets them.
The order between enrichment and value maps is fixed by the two events.
Within one event, listeners order themselves with TYPO3's `before` and
`after`.

Rejected: keeping the whole-factory swap as the only hook. Every copy loses
the upstream fixes to sub-record matching and hidden handling (ACE-242,
ACE-365). Also rejected: one event with a phase flag, because listeners would
have to branch on it.

### Nullable creation

The abstract method becomes
`createProfileFromFrontendUser(array $frontendUserData): ?Profile`. On `null`,
`createProfileForUser()` returns `null` without persisting or dispatching.
Subclasses that declare `: Profile` stay valid, because a covariant return
type narrows the parent's.

### A skip on update is not announced

A skipped profile is left out of `$updatedProfiles`, so no
`AfterProfileUpdateEvent` is dispatched for it. This matches the `skip_sync`
handling added in ACE-490.

### Listeners are stateless

Listeners are plain services with TYPO3's `#[AsEventListener]`
(`TYPO3\CMS\Core\Attribute\AsEventListener`), never Symfony's. The
documentation example fetches LDAP data per call and holds no cache.

### Decided: added keys carry a prefix by convention only

Keys that a listener adds to the frontend user source data follow the
documented convention `<source>.<key>`, for example `ldap.department`.
`setFrontendUserData()` does not enforce it.

The event cannot tell an added key from a real column that a value-map
listener legitimately rewrites without diffing the arrays, and the mapping of
`ace-tbd-settings-driven-fe-user-mapping` deliberately does not validate
mapped columns against `fe_users`. No TYPO3 column name contains a dot, so a
dotted key cannot collide with a column by construction. Rejected: rejecting
unprefixed added keys in the setter.

## Risks / Trade-offs

- [A listener that changes data for the wrong action] → `getAction()` is
  always set, and the documentation shows the create and update branches.
- [An enrichment listener is slow per user] → Same cost as today's LDAP
  factories. It is not made worse.

## Open Questions

None.
