## Context

See `proposal.md`. On `main`:

- `ProfileUpdateRequestService` is `final readonly`; `validate()` checks the
  login and then calls `findEditableProfile(int $profileUid)`, which loops over
  `ProfileRepository::findByFrontendUser()` for the current frontend user.
- `ProfileController::listAction()` calls `findByFrontendUser()` itself, and
  `indexAction()` calls `findEditableProfile()`. List and endpoints use the
  same finder, but in two places.
- The service does not exist on branch `2`; it is new in the unreleased 3.0.
- `AcademicPersonsEditProfileEditingAuthorizationTest` asserts 403 for a
  foreign profile on every endpoint family.

## Goals / Non-Goals

**Goals:**

- One method decides, and every entry point calls it.

**Non-Goals:**

- Caching the result across requests.

## Decisions

### An event that composes

`FGTCLB\AcademicPersonsEdit\Event\ModifyEditableProfilesEvent` (`final`) with
`getFrontendUserId()`, `getProfiles(): list<Profile>`,
`setProfiles(list<Profile>)`, `getRequest()`. The extension has no
`Classes/Event/` directory yet; this adds it.

Rejected: an interface with an `#[AsAlias]` default. Only one implementation
can win, so two extensions each granting a different case would exclude each
other; listeners compose.

### One method for list and endpoints

`ProfileUpdateRequestService::getEditableProfiles(ServerRequestInterface):
list<Profile>` builds the linked profiles, dispatches the event, and
de-duplicates by uid, keeping the order. `findEditableProfile()` takes the
request as a second argument and picks from that list; `listAction()` calls
`getEditableProfiles()`. The login check stays in front of both; for a
visitor without login the method returns an empty list without dispatching.

Rejected: a separate event for the list. List and endpoints would drift, which
is exactly the security defect the single method prevents.

## Risks / Trade-offs

- [A listener widens access by mistake] → The documentation states that a
  listener is an authorisation decision and shows an example that checks a
  relation, not a flag from the request.
- [A listener returns profiles in another language] → The service accepts
  only `Profile` objects and matches by uid, as today.

## Open Questions

- None.
