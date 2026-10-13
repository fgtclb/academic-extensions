## Context

Measured on `main` `e75f86b95` against the installed cores, 14.3.7 in
`.Build/vendor` and 13.4.35 in `core-13/vendor`.

- `academic-base/Classes/Domain/Model/Dto/PluginControllerActionContext.php`
  (`final`) returns the request attributes `site` (`getSite(): ?Site`, line
  48) and `language` (`getLanguage(): ?SiteLanguage`, line 53) unchecked.
  `getContentObjectRenderer()` and `getExtbaseRequestParameters()` already
  check with `instanceof` (lines 32 and 88, ACE-442).
- `getApplicationType(): ApplicationType` (line 43) calls
  `ApplicationType::fromRequest()`, which throws `\RuntimeException`
  1606222812 when the attribute `applicationType` is not an int, and
  `\LogicException` 1678875015 for an int that is neither the frontend nor the
  backend type. On v14 the install tool type resolves too, on v13 it is one of
  those ints. Same codes on both cores (`cms-core/Classes/Http/ApplicationType.php`).
- `NullSite` implements `SiteInterface`, `Site` does too, but a `NullSite` is no
  `Site`, so `getSite()` is a `TypeError` for it. The backend `SiteResolver`
  stores a `NullSite` for page id 0 and for pages outside a site
  (`cms-backend/Classes/Middleware/SiteResolver.php`, `matchByPageId()`).
  `SiteLanguage` is a plain class on both cores.
- The interface
  `academic-base/Classes/Domain/Model/Dto/PluginControllerActionContextInterface.php`
  is `@api` (ACE-749) and says nothing about the exception. The only mention is
  one sentence in
  `academic-base/Documentation/Changelog/3.0/Important-PluginControllerActionContextContentObject.rst`.
  The extension points page (`Documentation/Developers/ExtensionPoints/Index.rst`,
  "What an event hands over") lists the interface only.
- The deprecated persons class
  `academic-persons/Classes/Domain/Model/Dto/PluginControllerActionContext.php`
  has the same three bodies (lines 44 to 57). The persons interface extends the
  base one with an empty body, so it changes with it.
- The unit test
  `academic-base/Tests/Unit/Domain/Model/Dto/PluginControllerActionContextTest.php`
  pins the exception (`getApplicationTypeThrowsWithoutARequestType()`) and has
  no test for the site or the language. The persons test covers the content
  object only.
- No production code calls `getApplicationType()` of the context. One reads
  `getLanguage()`: `academic-persons/Classes/PageTitle/ProfileTitleProvider.php:135`,
  which builds the language service from it and falls back to the backend
  user's preferences when it is `null`.
- 3.0.0 is not tagged (`3.0.0-dev`). Branch `2` has the same three bodies.

## Goals / Non-Goals

**Goals:**

- Every getter that declares a nullable type answers `null` instead of
  failing.
- The one getter that can still raise is documented where a listener author
  looks: the interface and the extension points page.

**Non-Goals:**

- Changing any declared type under the recommended option.
- Touching how the controllers build the context (ACE-767).

## Decisions

### Site and language: an instanceof check, as for the content object

`getSite()` returns the attribute only when it is a `Site`,
`getLanguage()` only when it is a `SiteLanguage`, in both classes. It is the
shape `getContentObjectRenderer()` got in ACE-442 and needs no changed type.

Rejected: returning a `NullSite` as `SiteInterface`. It widens the declared
type, which breaks every listener that calls a `Site` only method, for a value
that says "no site" anyway.

### The application type, decision for the maintainer

**Decided 2026-10-13: option A.** Stefan chose to keep the type and document
the exception. He also chose to take the two type checks to branch `2` as a
bugfix of their own, without the application type.

**Option A, recommended: keep `ApplicationType` and document the exception.**
The interface method gets `@throws` for the two core exceptions and one
sentence on when they happen, the extension points page gets one sentence
in the row of the context, and `docs/architecture/list-plugin-events.md` one
in "Every event carries the plugin context". The unit test stays as it is.
Reason: a plugin the frontend renders always carries the attribute, the
frontend application sets it before any middleware. The exception needs a
request built by hand (a test, a command, a sub-request), and that request is
already not what core accepts as a TYPO3 request: core itself throws the same
exception for it on purpose ("should not be caught"). A nullable type would
cost every listener a null check for a case real rendering never produces,
and no code in the repository reads the value at all.

**Option B: `?ApplicationType`, with a Breaking entry.** The getter catches
nothing, it checks the attribute itself (`is_int()`, then
`ApplicationType::fromRequest()`), and returns `null` without it. The
interface changes its return type, so every own implementation of the
interface and every listener that uses the value unchecked breaks. Needs
`Breaking-PluginControllerActionContextApplicationTypeNullable.rst` in
`academic_base`, the inverted unit test, and has to be merged before 3.0.0 is
tagged, afterwards it waits for 4.0 together with ACE-747. With option B the
second requirement of the `academic-base/plugin-action-context` delta becomes
"reading the application type never fails" (none for a request without
one), and the apply step rewrites it with `/opsx:update` first.

**Option C: defer to 4.0.** Rejected as a default: the interface is `@api`
from 3.0 on, and documenting the exception costs one docblock line now.

Whatever is chosen, the exception is never caught inside the getter while the
type stays non-nullable: there is no application type to answer with.

### One changelog entry for both classes

The guard lands in the deprecated persons class as well, because listeners of
the persons title placeholder event receive that class. One `Important-` entry
in `academic_base` names both classes. The persons changelog gets none: the
persons class is `final` and deprecated, a listener sees it only through the
interface of `academic_base`.

### The title provider is left as it is

With the guard, `ProfileTitleProvider` falls back to the backend user's
language for a foreign value instead of failing, and to English without a
logged-in backend user. That is the branch it already
has for `null`, so it needs no change, only a sentence in the design and no
test of its own.

## Risks / Trade-offs

- [A listener relies on the `TypeError` to detect a wrong request] → none
  known, and a declared nullable return makes `null` the documented answer.
- [Option A leaves a getter that can take a request down] → documented on
  the interface and the extension points page, and only reachable with a
  request no TYPO3 application handled.
- [Option B chosen after 3.0.0 is tagged] → not possible in 3.x, it moves to
  4.0 with ACE-747. The prompt asks for the decision before any code.

## Migration Plan

- Listeners: nothing to do. A listener that caught the `TypeError` gets
  `null` instead.
- Option B only: implementers of the interface declare `?ApplicationType`, and
  listeners check the value before using it.

## Open Questions

None. The decision above was confirmed before the first code change.
