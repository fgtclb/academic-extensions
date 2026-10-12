## Context

See `proposal.md` for the motivation. Measured on `main` `e75f86b95` by
reading the code, not by running it:

- `Classes/Controller/ProfileController.php` of `academic-persons-edit`:
  3945 lines, 39 promoted `private readonly` constructor dependencies
  (`:245-285`), 113 methods. `eventDispatcher` and `validatorResolver` come
  on top, inherited from `ActionController`. It is the only class in
  `Classes/Controller/`.
- 19 actions registered in `ext_localconf.php:16-35`, cacheable and
  non-cacheable lists identical. 16 answer JSON (`JSON_ACTIONS`, `:169-186`).
  `initializeAction()` (`:294`) applies `assertRequestWasSentByTheEditor()`
  (`:328`) to the JSON actions and `uploadImage`, and sends everything else
  through the HTML access denied flow for a visitor who is not logged in.
  `errorAction()` (`:350`) answers JSON for `uploadImage` only.
- Profile actions `:619-922`, document actions `:923-1323`, contact actions
  `:1324-1650`, helpers of both families `:1651-3272`, image `:3333-3740`,
  write lifecycle and project fields `:3757-3945`.
- Shared by the document and the contact actions: the payload readers
  (`assertDocumentPayload()`, `getRequiredPositiveInteger()`,
  `getOptionalPositiveInteger()`, `getRequiredDocumentMode()`,
  `getSubmittedDocumentFields()`), `handleDocumentFailure()`,
  `dispatchBeforeWrite()`, `persistAndDispatchProfileUpdate()`,
  `sortItems()`, `throwJsonError()`, `findDocumentRecord()` (the contact
  request resolves its contract through it), `createDocumentField()` and
  `validateDocumentField()` (the contact fields are built and validated
  with them). `documentFormAction()` serialises the contacts of a contract
  through `getContractContactSections()`.
- The field helpers read the request only to translate (`translate()`,
  `:2059`, which hands the Extbase request to `LocalizationUtility` on v14
  so that a `_LOCAL_LANG` override of the plugin applies, see the spec
  `academic-persons-edit/label-overrides`) and to format a date for the site
  locale (`resolveSiteLocale()`). Every refusal is
  `throwJsonError()`, a `PropagateResponseException` with a `JsonResponse`.
- `Templates/Profile/Index.html:12-57` builds all 16 JSON addresses with
  `controller: 'Profile'` and page type 1733735, and hands them to the script
  as data attributes. The script builds no address of its own.
  `Partials/Profile/Image/Editor.html:22` and `Templates/Profile/List.html`
  address `Profile` actions that stay.
- An action not registered for the addressed controller fails in
  `RequestBuilder` with `InvalidActionNameException` 1313855175 on v13.4 and
  v14.3 alike, unless `mvc.callDefaultActionIfActionCantBeResolved` is set,
  which the extension does not do.
- Tests that pin the current shape: `profileEditingPluginRegistersOnlyTheNewControllerActions`
  in `AcademicPersonsEditProfileEditingTest` (`:2459-2510`, one controller
  and its action list), `everyActionNamesAnActionOfTheEditor` in the unit
  test of `BeforeProfileEditingWriteEvent` (`:120`), the unit test
  `ProfileControllerTest` (constructs the controller with all
  dependencies), `everyPluginControllerIsFound` in `ExtensionPointTest` of
  `packages-dev/monorepo-shared` (9 controllers).
- `AcademicPersonsEditProfileEditingAuthorizationTest` reads the endpoint
  addresses from the rendered page, so it follows a template change by
  itself. Its foreign profile, anonymous and header tests share
  `endpointFamilyProvider()` (`:144`), which lists 12 of the 16 JSON
  endpoints: `updateDocument`, `deleteDocument`, `updateContractContact`
  and `deleteContractContact` are missing.
- 33 functional test classes with 206 tests in `Tests/Functional/Plugins/`
  drive the endpoints over HTTP.
- 3.0.0 is not tagged (the last tag is 2.3.4). The 3.0 changelog entries
  `Breaking-ReplacedProfileEditingPlugin.rst` and `Feature-HideDocumentRows.rst`
  name the JSON actions as actions of `ProfileController`, and the manual
  chapter "Profile editing" says the plugin registers only
  `ProfileController`.

## Goals / Non-Goals

**Goals:**

- Each controller holds its actions, its request parsing and its answers,
  and nothing a service can do without the controller.
- Every moved line is moved, not rewritten: a reviewer reads both commits
  with `git diff --color-moved`.
- The guard stays enforced by a test per endpoint, not by convention.

**Non-Goals:**

- Splitting `ProfileUpdateRequestService` or the image handling.
- Dropping the v13 branch of the label translation (it goes with v13).

## Decisions

### Decision for the maintainer 1: both steps, before the 3.0.0 tag

- (a) Both steps, step 2 before 3.0.0 is tagged. **Recommended.** The
  addresses change while no release has them, and every later editor
  change lands in a controller of a quarter of today's size.
- (b) Step 1 now, step 2 after 3.0.0. Step 2 then needs a `Breaking-` entry
  and `[!!!]` for every site with a template override.
- (c) Step 1 only. Most of the size goes, the three endpoint families keep
  sharing one class and one constructor.

Part of the same decision: the controller names, which an integrator sees in
the addresses. Recommended `Document` and `ContractContact`, short and
unique within the one plugin of the extension. Rejected `ProfileDocument`
and `ProfileContractContact`: the plugin namespace already says profile
editing.

### Decision for the maintainer 2: one change, two commits, two stacked pull requests

Recommended: this one change, implemented as two commits, step 1
`[TASK] ACE-507: Extract editor field handling` and step 2
`[TASK] ACE-507: Split the profile editor controller`, plus the archive
commit. Opened as a stack of two pull requests (section "Stacked pull
requests" of `docs/workflow/pull-requests.md`), so the gates report each step on its own, and merged together, since the
archive sits on top of step 2. One issue, one plan, one review context, and
step 2 is planned on the services of step 1.

Rejected: two changes. They need a second issue key or two changes under one
key, duplicate the context, and step 2 could not be planned without step 1
merged. If option (c) above is chosen, `/opsx:update` drops step 2, the spec
and the template tasks, sets `skip_specs: true`, and the archive commit sits
on step 1.

### Decision for the maintainer 3: the changelog

Recommended: no new entry. The 3.0 entries that name the actions as actions
of `ProfileController` are corrected in the same change, since they describe
a state no release had. Rejected: a new `Important-` entry, which would
announce a change against an unreleased state. If 3.0.0 is tagged before
step 2 lands, step 2 instead adds
`Breaking-EditorEndpointsAddressedPerFamily.rst` and the commit carries
`[!!!]`.

### Step 1: what moves into services

The rule: everything below the actions that needs no controller state.
Proposed services, `final readonly class`, `@internal`, below
`Classes/Service/` (names are proposals, two may be merged where the split
gains nothing):

- `EditorLabels`: `translate()` with its v13/v14 switch and the `@todo` to
  drop it with v13.
- `EditorFieldValidation`: `validateDocumentField()`, the character limit
  and the configured validators, through an injected `ValidatorResolver`.
- `DocumentFieldDefinitions`: the contract and profile information field
  definitions, their options and display values, the date parsing, the
  managed field locking and `serializeDocumentItem()`.
- `DocumentFieldNormalizer`: `normalizeAndValidateDocumentFields()`, the
  value normalisation and the select entity lookup.
- `DocumentRecords`: listing including hidden records, lookup by uid, next
  sorting value, the complete order check and the reordering.
- `ContractContactFields`: field definitions, country and type options,
  item and summary serialisation, the contact sections of a contract and
  the normalisation.
- `ContractContactRecords`: lookup, listing, next sorting value, and the
  create, update, add, remove and visibility writes over the three record
  types, which takes six factories and repositories out of the controller.

The payload readers, the action permission checks and the form data
creation stay in the controller in step 1, since they answer the request.

**The request is an argument.** Services that translate or format take the
Extbase request as a method argument. On v14 `LocalizationUtility` reads the
plugin `_LOCAL_LANG` from it, and the site locale comes from its language.
Rejected: reading `$GLOBALS['TYPO3_REQUEST']` or keeping the request in a
service, which is state in a shared service.

**Refusals are an exception, not a response.** A service refuses with
`Service/Exception/EditorRequestException`, `final`, `#[Exclude]`,
carrying error identifier, status, message and field errors. The controller
turns it into today's JSON answer, in a `catch` placed before the
`catch (\Throwable)` of every action. Rejected: services throwing
`PropagateResponseException`, which puts HTTP into services and makes a unit
test decode a response. Rejected: result objects, which every caller would
have to check.

**Wiring.** `Configuration/Services.yaml` loads `Classes/*` with
autowiring, so a private service injected into a controller needs no entry
and no attribute. That matches the existing services of the extension, which
carry an attribute only for an alias or a public service. A data object or
the exception carries `#[Exclude]`.

### Step 2: three controllers of one plugin

- `DocumentController` and `ContractContactController`, `final`,
  `@internal`, constructor injection. Registered in the same
  `configurePlugin()` call after `ProfileController`, so `list` stays the
  default action, with identical cacheable and non-cacheable lists.
- Every action of the two is JSON, so their `initializeAction()` applies the
  guard without an action list and never the HTML login flow.
  `ProfileController` keeps its list for its four JSON actions and
  `uploadImage`, and `errorAction()`.
- Shared, as stateless services: `EditorEndpoint` (the guard, the JSON error
  answer, the payload readers both families use, the failure logging) and
  `EditorWrites` (the write offer to `BeforeProfileEditingWriteEvent`,
  `persistAll()`, the optional write before the announcement, and
  `AfterProfileUpdateEvent` with its translation guard). The exception codes
  1777046203 and 1776760205 stay.
- Rejected: an abstract base controller. Its constructor would be inherited
  API, the class design rules ask for method injection there, and a
  subclass overriding `initializeAction()` without calling the parent drops
  the guard silently. Rejected: a trait, which cannot hold injected
  collaborators and is just as silent. A service call can be missed too, so
  the per-endpoint tests are what holds the guard.
- `ProfileEditingAction` keeps its values: they are action names, and those
  do not change.

### Order against the pending editor changes

- `ace-tbd-editor-translation-writes` changes
  `persistAndDispatchProfileUpdate()` and the structural document and
  contact endpoints, which step 2 moves. **ACE-507 first**: the translation
  change then edits one method of `EditorWrites` and two small controllers
  instead of rebasing over the move.
- `ace-tbd-editor-editable-profiles-event` changes
  `ProfileUpdateRequestService` and `listAction()`, which stay. Either order
  works.
- ACE-761 (open bug: the editor deletes the default-language record from a
  translated language) is in the same document and contact endpoints and
  is to be redesigned together with `ace-tbd-editor-translation-writes`.
  If it is fixed first, its code moves with the actions. If it is fixed
  later, it lands in the two new controllers and `EditorWrites`.
- Both are postponed in the project differences pipeline, and ACE-507 is
  part of the board round before #10. This change corrects the
  method locations their `design.md` names, in the same pull request.

## Risks / Trade-offs

- [A new controller misses the guard] → `endpointFamilyProvider()` is
  widened to all 16 JSON endpoints for the header and the anonymous test,
  and removing the guard from each new controller is shown red.
- [A service exception falls into `catch (\Throwable)` and becomes a 500]
  → the existing 422 tests of every family (`validation_failed`,
  `write_refused`) run after step 1 on both cores.
- [A label loses the plugin override on v14] → the request argument, and
  `AcademicPersonsEditLabelOverrideTest` on v14.
- [A template override keeps the old addresses] → the spec scenario, the
  manual chapter "Customizing the view", and no release had them.
- [A listener reads the controller name] → named in the Developers chapter.
- [The failure log names another class] → the log entries of document and
  contact failures name the new controller. Stated in the commit message.
- [The diff hides a change] → two commits, moves only, `--color-moved`
  review, the functional suite unchanged on SQLite, PostgreSQL and MariaDB.

## Migration Plan

None. No schema, record or setting changes. A revert of the commits restores
the previous state.
