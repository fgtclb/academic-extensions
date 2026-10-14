## Context

See `proposal.md` for the motivation. Measured on `origin/2` at `a2caf7bd5`
against TYPO3 12.4.45 (PHP 8.1) and 13.4, and compared with the `main` change
(`c1c9e8725`..`a68826372`). Line numbers are those of `2`.

ACE-434:

- `academic-programs/Classes/Factory/ProgramDataFactory.php:22-31` reads ten
  keys without a fallback. The application link columns of ACE-777 do not
  exist on this branch. The `DemandFactory` of partners (`:44`), programs
  (`:47`) and projects (`:45`) reads `(int)$contentElementData['uid']` when
  `settings.categories` is set, and every list controller hands in `[]`
  without a content object.
- Item 1 is done by ACE-102, item 3 by the backport of ACE-438 (`438ba5f38`,
  `trimExplode('=', $type, false, 2) + [1 => '']` in
  `Types/AbstractTypes.php:61` and `DemandValues/AbstractDemandValues.php:61`).

ACE-435:

- There is no `AfterSaveResolver` on this branch.
  `academic-jobs/Classes/Controller/JobController.php::createAction()` reads
  the listener values (`:268-269`), applies the deprecated `listPid` fallback
  only for `null` (`:273-285`), asks the mode (`:306`) and redirects for any
  non null page (`:324-327`), otherwise `redirect('list')` (`:330`).
- `FlashMessageCreationMode::shouldBeCreated()` already counts a page of 0
  or below as none (`SaveForm/FlashMessageCreationMode.php:26`), its docblock
  (`:21`) does not say so. The controller comment (`:297`) names a mode
  `SUPPRESS_WHEN_REDIRECTED` that does not exist. `resolveRedirectPageId()`
  maps settings of 0 or below to `null`, `resolveFlashMessageCreationMode()`
  guards `tryFrom()` with a redundant `>= 0` twice (`:476`, `:487`).
- On TYPO3 v12 a target page of 0 builds no link: `LinkFactory` treats the
  parameter `'0'` as empty (`cms-frontend/Classes/Typolink/LinkFactory.php:176`),
  `createUrl()` returns `''` (`ContentObjectRenderer.php:4552-4560`) and
  `redirectToUri()` prefixes the request directory
  (`ActionController.php:775-778`, `GeneralUtility.php:2073-2091`). On v13
  `main` measured the domain root.
- v12 and v13 pass an Extbase redirect to `header()` and answer the frontend
  request with 200 (`cms-extbase/Classes/Core/Bootstrap.php:191-202` on v12).
  `Dispatcher::dispatch()` dispatches `AfterRequestDispatchedEvent` with the
  request and the final response (`Mvc/Dispatcher.php:79` on v12).
- `ListenerProviderPass` reads the event of a tag without `event:` from the
  `__invoke()` parameter of an autowired service
  (`cms-core/Classes/DependencyInjection/ListenerProviderPass.php:83`,
  `:113-150` on v12). `TYPO3\CMS\Core\Attribute\AsEventListener` does not
  exist on v12.

ACE-436: `Partner::getUid(): int` (`academic-partners/Classes/Domain/Model/Partner.php:63`),
`getAttributes()` (`:236-240`), `Project::getPid(): int` and `getUid(): int`
(`academic-projects/Classes/Domain/Model/Project.php:42`, `:47`). Four baseline
entries in `Build/phpstan/Core12` (`:53-61`, `:178-186`) and four in `Core13`
(`:68-76`, `:188-196`). `AbstractDomainObject::getUid(): ?int` on v12 and v13.
`GeocodeCommand` passes the uid to `reportResult(..., int $partnerUid, ...)`
(`:75`, `:142`, `:154`) and has an unreachable null check in
`writeGeocodeResult()` (`:200-205`).

ACE-441: `Profile::getHtmlSanitizer()` (`academic-persons/Classes/Domain/Model/Profile.php:551-558`)
keeps the sanitizer in a method static. `typo3/html-sanitizer` 2.3.2 on v12:
`CommonBuilder::build()` and `createBehavior()` are overridable,
`Sanitizer::sanitize(string, ?InitiatorInterface)`.

ACE-443 and ACE-444: as measured in the backport analysis. The collection
(`academic-programs/Classes/Collection/FileReferenceCollection.php`) is
`final`, not `@internal`, shipped in 2.2 and 2.3. `ProfileFactory::createFromFormData()`
of `academic_persons_edit` is `@internal` and only called by a test. On v12
`FileRepository::findByRelation()` (`cms-core/Classes/Resource/FileRepository.php:72-141`)
queries with the frontend restrictions in the frontend and uses the relation
handler with the workspace of the context elsewhere.

## Goals / Non-Goals

**Goals:**

- One commit per issue, each green on its own for v12 and v13.
- Every behaviour change proven by a test that is red without it, on v12 and
  on v13.
- Four phpstan baseline entries fewer per core version, none added.

**Non-Goals:**

- Backporting the infrastructure of `main` the fixes rely on there:
  `AfterSaveResolver`, the trait helpers of ACE-723, the upgrade check.

## Decisions

### 1. Commits

| # | Subject                                              | Footer                                  |
|---|------------------------------------------------------|-----------------------------------------|
| 1 | `[BUGFIX] ACE-434: Guard program and demand reads`   | `Resolves: ACE-434`, `Related: ACE-438` |
| 2 | `[BUGFIX] ACE-435: Treat redirect page 0 as none`    | `Resolves: ACE-435`                     |
| 3 | `[BUGFIX] ACE-436: Allow no uid on unsaved entities` | `Resolves: ACE-436`                     |
| 4 | `[TASK] ACE-441: Build profile sanitizer per setter` | `Resolves: ACE-441`, `Related: ACE-425` |
| 5 | `[TASK] ACE-443: Deprecate coverage round dead code` | `Resolves: ACE-443`, `Related: ACE-444` |
| 6 | `[TASK] ACE-444: Fix the coverage round slips`       | `Resolves: ACE-444`                     |

The archive is the last commit. No `[!!!]`: nothing public is removed or
renamed in a minor release.

### 2. ACE-434: fall back per key

`?? 0` and `?? ''` on the ten reads, and `(int)($contentElementData['uid'] ??
0)` in the three demand factories, as on `main`. A full row maps exactly as
before. Rejected: throwing for a record that is no page, the processor may be
configured on one.

### 3. ACE-435: normalise in the controller, after the fallback

`createAction()` maps a redirect page of 0 or below to `null` after the
`listPid` fallback and before the mode and the redirect use it, so both agree
and a 0 from a listener never reaches the fallback. Rejected: normalising in
`setRedirectPageId()`, a later listener would read something else than the
earlier one set. Rejected: backporting `AfterSaveResolver`, a 3.0 refactoring.
The docblock of `shouldBeCreated()` names the `> 0` condition, the comment
names `SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE`, the two `>= 0` guards go,
`tryFrom()` already rejects a negative value. An `Important` entry.

The proof is a functional test without a version group, the same for v12 and
v13. Its fixture extension `test_job_after_save_listener` registers both
listeners by tag in `Services.yaml`. The response of the plugin is read from
`AfterRequestDispatchedEvent`, because neither version returns the redirect,
and the post is sent as the notification mail tests send it.

### 4. ACE-436: match the parent type

`?int` on the three getters with the docblocks of the parent,
`Partner::getAttributes()` returns an empty collection without a uid, and
`GeocodeCommand` refuses a partner without a uid before the Nominatim request
and keeps its `int` parameters. No changelog entry.

### 5. ACE-441: one sanitizer per setter call

The commit of `main` unchanged: the setters and `ProfileTest` are identical.
It is backported without ACE-425 so a later backport of ACE-425 is a straight
port.

### 6. ACE-443: deprecate the collection

`@deprecated since 2.4, will be removed in 3.0` on the class and on
`getCollectionByPageIdAndField()`, and an `E_USER_DEPRECATED` naming
`FileRepository::findByRelation()`. Every test of `FileReferenceCollectionTest`
asserts the notice with a capture helper, as `ProfileFormDataTest` does,
because PHPUnit 10, installed on this branch for v12 and v13, has no
`expectUserDeprecationMessage()`. Unlike there, the tests carry no
`#[IgnoreDeprecations]`: the captured notice never reaches PHPUnit, so the
attribute would only hide other deprecations. A
`Deprecation` entry. ACE-444 item 10 in the same file: `@throws
\Doctrine\DBAL\Exception`, the unused import and parameter go. Rejected:
removing it, a break in a minor release. `createFromFormData()` and its test
are removed. Items 3 and 5 stay, item 4 won't fix, item 6 is done by the
backport of ACE-748.

### 7. ACE-444: slips, no rename

Nine headers, `@toodo`, strict types in `Settings/Validation.php` only.
`ProfileInformationType::__set_state(): self`, and `isValid()` requires a
field name. Item 8 documentation only: on this branch two validations meet
on one column only through two spellings of one property in one set, and the
later one decides the TCA. Items 9, 12 and 13 stay.

## Risks / Trade-offs

- [A listener on a project relied on 0 to reach the root of the domain] →
  `Important` entry, the setting and the plugin field already meant none.
- [A project calls the collection] → the deprecation warns before 3.0
  removes it.
- [ACE-425 changes the same setters as ACE-441] → whichever lands second
  rebases.
