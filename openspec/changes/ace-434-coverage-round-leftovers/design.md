## Context

See `proposal.md` for the motivation. Measured on `main` at `e75f86b95` and on
`origin/2` at `7ebded7b8` (board triage of 2026-10-12, re-checked while
writing this change). Line numbers are those of `main` unless stated.

ACE-434, unguarded array reads:

- Item 1 (B-ITE job service) is done on both branches by ACE-102, the guard of
  the program page data processor by ACE-786 (`main`) and ACE-788 (`2`).
- Item 2: `academic-programs/Classes/Factory/ProgramDataFactory.php:22-31`
  reads ten keys without a fallback. Only the two columns of ACE-777 have one
  (`:34-35`). The functional `ProgramDataFactoryTest` exists and covers full
  page rows only.
- Item 3: `AbstractTypes.php:60` and `AbstractDemandValues.php:50` of
  `academic_persons` destructure `trimExplode('=', $type, true, 2)`, so
  `private=` and `=Private` read an undefined index. The change
  `ace-438-persons-list-ordering-input` (ACE-438, pipeline #124, ahead of this
  one) already plans the rule for it: an entry without a label is labelled
  with its value, an entry without a value is ignored. This change does not
  plan it a second time.
- Item 4: the `DemandFactory` of projects (`:45`), partners (`:45`) and
  programs (`:43`) reads `(int)$contentElementData['uid']` when
  `settings.categories` is set, while the neighbouring keys are guarded.

ACE-435, redirect page 0:

- `academic-base/Classes/Form/FlashMessageCreationMode.php:29` counts a
  redirect page of 0 or below as none. Its docblock (`:21-26`) omits that
  condition and is missing a word.
- `AfterSaveResolver::decide()` (`:63-72`) passes the redirect page through
  unchanged. Its docblock names the disagreement and ACE-435.
  `redirectPageId()` (`:31-42`) already maps settings of 0 or below to `null`,
  so only a listener of `AfterSaveJobEvent` reaches the gap. The setter of the
  event (`AfterSaveJobEvent.php:70-74`) validates nothing.
- `JobController::createAction()` applies the deprecated `listPid` fallback
  only when the listener left `null` (`:464`), then calls `decide()` (`:496`)
  and redirects for any non null page (`:515-516`). Its comment (`:487`)
  names a mode `SUPPRESS_WHEN_REDIRECTED` that does not exist.
- `decide()` has no other caller. The "redirect page zero" rows of
  `AfterSaveResolverTest` (`:111`) pin the disagreement.
- Without a redirect page the plugin redirects to its own form page, and the
  message is shown there (`AcademicJobsLabelOverrideTest`).

ACE-436, uid typed `int`:

- `Partner::getUid(): int` (`academic-partners/Classes/Domain/Model/Partner.php:66`)
  with a docblock `int<0, max>|null`, while `AbstractDomainObject::getUid()`
  is `?int` on 13.4.35 and 14.3.7. `getAttributes()` (`:239-243`) passes it to
  `CategoryRepository::findByGroupAndPageId(string, int)`.
- `Project::getPid(): int` and `getUid(): int`
  (`academic-projects/Classes/Domain/Model/Project.php:46`, `:51`), and
  `getAttributes()` casts `(int)$this->uid` (`:123`).
- Four baseline entries per core version: two for `Partner.php`
  (`Core13|Core14/phpstan-baseline.neon:54-61`), two for `Project.php`
  (`:159-166`).
- `GeocodeCommand` hands `$partner->getUid()` to `reportResult(..., int
  $partnerUid, ...)` (`:75`, `:141`, `:153`). phpstan runs at level 8, so the
  nullable type reaches it.
- Nothing in production builds an unsaved partner or project.

ACE-441, sanitizer in a method static:

- `Profile::getHtmlSanitizer()` (`academic-persons/Classes/Domain/Model/Profile.php:563-570`)
  is `protected`, `Profile` is not `final`, and the sanitizer is kept in a
  `static` local. Five rich text setters call it once, seven storage setters
  three times per element (`:296-495`).
- `TYPO3\HtmlSanitizer\Builder\CommonBuilder` is neither a singleton nor a
  service, and `build()` creates a new behaviour on every call. The issue
  text claims otherwise.
- `ProfileTest` pins what the sanitizer does to values, including the encoded
  link of ACE-425 (`anAmpersandInAProfileInformationLinkIsHtmlEncoded()`).

ACE-443, dead code:

- Item 1: `academic-programs/Classes/Collection/FileReferenceCollection.php`
  is `final`, not `@internal`, and has no caller outside
  `Tests/Functional/Collection/FileReferenceCollectionTest.php`. The 2.4
  changelog `Important-PageMediaFollowsTheReferenceSorting.rst` names it. The
  four `Build/phpunit/FunctionalTestTimes-*.json` list its test.
- Items 2 and 3 are done on `main` by ACE-262.
- Item 4: the `UnknownValidatorException` guard of `JobValidator.php:58-63`
  stays unreachable, `Validation` is `@internal` and only the normalizer fills
  its validator list. The twin guard of `academic_persons_edit` is unit
  tested.
- Item 5: `PartnerItems.php:27` checks `$partners !== null` although
  `PartnerRepository::findAll(): ?QueryResult` (`:39`) returns
  `$query->execute()`. The repository is not `final`, and its `findAll()`
  carries a baseline entry for the declared type on both core versions.
  `ace-672-partner-select-offers` (ACE-672, pipeline #125, ahead of this one)
  reworks the same method. Its proposal sets the query flag in the select and
  leaves `findAll()` and the null check as they are, so the check is expected
  to be still there.
- Item 6: `academic-persons/Tests/Functional/Domain/Model/Dto/PluginControllerActionContextTest.php:45`
  has no `#[Test]` at `e75f86b95`. A scan of all test classes on both branches
  found no other. ACE-748 added the attribute since (`990a49bb5`).

ACE-444, small slips, open on `main`:

- Item 1: seven files of `academic_persons` say they belong to
  `academic_persons_edit`: `Domain/Repository/LocationRepository.php`,
  `Domain/Repository/ProfileInformationRepository.php`,
  `Event/ChooseProfileFactoryEvent.php`, `Profile/ProfileFactory.php`,
  `Profile/ProfileFactoryInterface.php`, `Command/CreateProfilesCommand.php`,
  `Configuration/TCA/Overrides/fe_users.php`.
- Item 3: `@toodo` in `Profile.php:546`.
- Item 4: the namespace `Domain\Model\Dto\Syncronizer\` and the promoted
  property `$recordSyncronizer` of `SynchronizerContext`, used by
  `RecordSynchronizer`, `RecordSynchronizerInterface` (`@internal`), the
  listener `SyncChangesToTranslations` of `academic_persons_edit` (property
  and named argument), tests of three extensions, the test class
  `RecordSyncronizerTest`, `Documentation/Developers/Index.rst:777` and
  `docs/architecture/class-design.md:223`.
- Item 5: `SortingOptions::SORT_BY_STARTDATE_desc`
  (`academic-projects/Classes/Enumeration/SortingOptions.php:24`). FlexForm,
  routes and the sorting select store and read the value, never the name.
  `getConstants()` reflects every constant, so an alias constant would list
  the option twice. `SortingOptionsTest.php:37` names it.
- Item 8: `TcaValidationMerger::toTcaTableConfig()` (`:44`) assigns the
  `config` fragment of each validation, so the later of two validations on one
  column replaces the earlier. `twoValidationsOnOneColumnKeepTheLastTcaConfig()`
  pins it. The normalizer writes `readOnly` and `required` into every
  fragment, also when they are `false`.
- Item 10 goes with ACE-443 item 1. Items 2, 7, 12 and 13 are moot on `main`,
  items 9 and 11 are fixed. Item 6 is proposed as won't fix.

## Goals / Non-Goals

**Goals:**

- One commit per issue, each resolving its key and green on its own.
- Every behaviour change proven by a test that is red without it.
- No new phpstan baseline entry, five entries fewer per core version: the
  four of ACE-436 and the one of `PartnerRepository::findAll()`.

**Non-Goals:**

- A check that compares file headers with the extension key. The headers
  are corrected, the header gate stays as it is.
- Nullable parent setters of the five persons models (ACE-444 item 6).

## Decisions

### 1. Commit order and tags

| # | Commit subject (at most 52 characters)                 | Footer                                  |
|---|--------------------------------------------------------|-----------------------------------------|
| 1 | `[BUGFIX] ACE-434: Guard program and demand reads`     | `Resolves: ACE-434`, `Related: ACE-438` |
| 2 | `[BUGFIX] ACE-435: Treat redirect page 0 as none`      | `Resolves: ACE-435`                     |
| 3 | `[BUGFIX] ACE-436: Allow no uid on unsaved entities`   | `Resolves: ACE-436`                     |
| 4 | `[TASK] ACE-441: Build profile sanitizer per setter`   | `Resolves: ACE-441`, `Related: ACE-425` |
| 5 | `[!!!][TASK] ACE-443: Remove coverage round dead code` | `Resolves: ACE-443`, `Related: ACE-672` |
| 6 | `[!!!][TASK] ACE-444: Fix the coverage round slips`    | `Resolves: ACE-444`                     |
| 7 | `[TASK] ACE-434: Archive the coverage round leftovers` | `Related:` each of the six keys         |

The defects go first, so each is reviewable without the clean-up around it.
ACE-443 comes before ACE-444 because removing the collection settles ACE-444
item 10. The renames sit in the last code commit. All seven commits go into
one pull request, as the maintainer confirmed on 2026-10-14.

### 2. ACE-434: fall back per key, as ACE-777 did

`?? 0` and `?? ''` on the ten reads of the program data factory, the shape of
`:34-35`, and `(int)($contentElementData['uid'] ?? 0)` in the three demand
factories. A full page row and a content element row map exactly as before.
Rejected: throwing for a row that is no page. The processor is configured by
integrators on records that are not pages, and the factory is used before the
database compare, the case ACE-777 already accepted.

Item 3 stays with ACE-438. If that change has not landed or dropped the rule,
this change is updated first instead of planning the same rule twice.

### 3. ACE-435: normalise in the decision, not in the event

`decide()` maps a redirect page of 0 or below to `null` before it asks the
mode, so redirect and message agree. The deprecated `listPid` fallback runs
before `decide()` and only for `null`, so a 0 from a listener does not reach
it, and the visitor returns to the form page as for no redirect page.
Rejected: normalising in `setRedirectPageId()`. A later listener would read
something other than the earlier one set, and a 0 would then fall into the
deprecated `listPid` fallback. The docblock of `shouldBeCreated()` names the
`> 0` condition, and the controller comment names
`SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE`. An `Important` entry in
`academic_jobs`, because a listener's 0 now ends on the form page.

### 4. ACE-436: match the parent type, change no caller semantics

`Partner::getUid(): ?int`, `Project::getUid(): ?int`, `Project::getPid():
?int`, with the docblocks of the parent. `Partner::getAttributes()` returns an
empty collection for an unsaved partner. `Project::getAttributes()` keeps its
cast, a page id of 0 has no categories. `GeocodeCommand` keeps its `int`
signature and reads the uid once per partner, which is always set for a
partner it found in the database, with no new baseline entry. Its null check,
unreachable in `writeGeocodeResult()`, moves in front of the Nominatim
request: a partner without a uid is refused before a request is spent on it,
and the command ends with a failure. A subclass that
overrides a getter with `int` stays compatible. No changelog entry: code typed
against the Extbase entity already had to handle `null`, and every partner and
project the extensions render carries a uid.

### 5. ACE-441: one sanitizer per setter call

Each sanitizing setter builds the sanitizer once into a local variable and
uses it for every value it sanitizes. `getHtmlSanitizer()` stays as the
`protected` seam and returns a new sanitizer on every call. Rejected: building
one per `sanitize()` call, three builders per storage element for nothing, and
injecting a builder, which an Extbase entity cannot take. Output is
unchanged. The core preset of `SanitizerBuilderFactory` is not adopted here,
it changes output for installations with a preset of their own and belongs to
ACE-425.

### 6. ACE-443: remove on `main`, item 4 won't fix

- The collection and its functional test are removed, their rows leave the
  four functional test time files, and a `Breaking` entry in
  `academic_programs` names `FileRepository::findByRelation()` of the core as
  the replacement. A deprecation first is not needed on `main`, the 3.0 major
  removes public API with a `Breaking` entry throughout. Branch `2` deprecates
  instead.
- Item 5 is done here, ACE-672 left the check in place: the `if` goes and
  `findAll()` declares the result interface the query returns, which also
  removes its baseline entry. That is a signature change of a repository that
  is not `final`, so it gets a `Breaking` entry in `academic_partners`.
- Item 6: ACE-748 added the attribute, so the test is only shown to fail once
  against another request object here.
- Item 4: won't fix. The guard costs nothing, removing it would ask for its
  unit tested twin in `academic_persons_edit` as well, and neither is
  observable.

### 7. ACE-444: two validations on one column

Every fragment carries `readOnly` and `required`, so a recursive merge, the
fix the triage proposed, lets the later field reset `required` to `false`
while keeping the earlier `minitems`. That is worse than today. Three ways
were weighed: combine the flags per column as the normalizer combines the
flags of one field (A), keep the later field winning and say so (B), or the
recursive merge (C, rejected for the reason above).

The maintainer chose B on 2026-10-14. The merger stays as it is, and the
existing unit test that pins the later fragment stays the proof. What
changes is that the loss is no longer silent:

- The persons settings manual says that two fields naming one column leave
  the column with the flags of the field applied later, the profile fields in
  section order and then the special fields.
- `academic:upgrade:check` reports a column that two fields of the persons
  settings share, as a warning naming the column and both fields. The check
  lives in `academic_base` and cannot read the settings of another extension,
  so `ConfigurationChecker` gains an `@internal` interface for finding
  providers, collected by a service tag, and `academic_persons` registers one.
  Internal rather than an extension point: it exists for the academic
  extensions only, and the extension points page stays as it is. The status
  report entry of the upgrade check shows the finding as well, since it lists
  every finding of the check.
- The persons settings entry of the status report names the same column with
  the same severity.

Only the persons settings can produce the case: they let an integrator set
`fieldName`, while `academic_jobs` derives every column from the property
name. The check looks at the same validation sets the persons settings merge
into a table: the profile fields with the special fields, the contract fields,
the three contact sets and the profile information sections per record type.
Two cases stay out of its reach, and the manual, `docs/` and the finder name
them: fields that collapse while the settings are built (two fields of one
property name, a special field named like a profile property) never reach a
set, and the listener judges project fields against the TCA before the merge
while the reports read the merged TCA. The merge can turn a column into an
`email` or `number` column, so the two can judge a project field
differently, which changes what is reported only where the field shares
the column with another field. Decision B asks for a hint without a
behaviour change, so neither is closed here.

No changelog entry: nothing changes for a configuration that works, and the
hint is found by the check itself. The `academic-persons/validation-flags`
delta is dropped, deltas for `academic-base/upgrade-configuration-check` and
`academic-persons/settings-overrides` take its place.

### 8. ACE-444: the two renames

- `Dto\Syncronizer\` to `Dto\Synchronizer\`, and `$recordSyncronizer` to
  `$recordSynchronizer` everywhere, including the property and the named
  argument of the persons edit listener, the test class and the two
  documentation places. No class alias: the promoted property and the named
  argument cannot be bridged, so an alias would only hide half of the break.
- `SORT_BY_STARTDATE_desc` to `SORT_BY_STARTDATE_DESC`, without an alias
  constant (it would show the option twice). Stored values do not change, no
  migration.

The maintainer chose both renames on `main` on 2026-10-14, with one
`Breaking` entry each in `academic_persons` and `academic_projects`. 3.0 is
the cheapest moment, and the interface is `@internal` already. Branch `2`
keeps the names. Commit 6 is `[!!!][TASK] ACE-444: Fix the coverage round
slips`.

### 9. Backport to branch `2`

A change of its own, re-derived from a backport analysis
(`docs/workflow/backporting.md`), with the v12 and v13 gates and entries in
`Changelog/2.4/`:

- ACE-434: items 2 and 4 as on `main` (partners `DemandFactory.php:44`,
  programs `:47`, the factory without the ACE-777 columns). Item 3 with the
  backport of ACE-438.
- ACE-435: the decision lives in `JobController::createAction()` there
  (`:306` against `:324`). Normalise after the `listPid` fallback and before
  both uses, fix the docblock (`:21`) and the comment (`:297`), and drop the
  two `>= 0` guards in front of `tryFrom()` (`:476`, `:487`). The controller
  has no unit seam, so the proof is a functional test with a fixture listener.
- ACE-436: the same commit, `Partner.php:63`, `Project.php:42` and `:47`,
  baselines `Core12` and `Core13`.
- ACE-441: only together with a backport of ACE-425, it has no user effect on
  its own.
- ACE-443: item 1 deprecated instead of removed (`E_USER_DEPRECATED` and a
  `Deprecation` entry, the tests expect the deprecation as
  `ProfileFormDataTest` does), and the import and the unused parameter of
  ACE-444 item 10 corrected there. Item 2 removed with its test, the class is
  `@internal`. Item 5 stays, `findAll()` keeps its `?` there and phpstan
  would report the loop over a nullable result. Item 6 as on `main`. Item 3
  stays by design.
- ACE-444: the headers of nine files, `@toodo`, strict types in
  `Settings/Validation.php`. Item 8 only with the decision of `main`, in
  `AcademicPersonsSettings.php:78`. Items 7, 12 and 13 are optional, item 9 is
  the documented contract of 2.x. No rename.

## Risks / Trade-offs

- [The changes of ACE-438 (#124), ACE-672 (#125) and ACE-624 (#129) touch the
  same parsers, the partner select and `PartnerRepository`] → this change is
  implemented after ACE-438 and ACE-672 and re-checks each premise on `HEAD`.
  ACE-624 is postponed to 3.1.0 and lands after this change. It changes how
  the list repositories order by "last updated", not `SortingOptions` of
  `academic_projects`, so the constant rename and it do not overlap. In
  `PartnerRepository` it changes `findByDemand()`, task 6.3 changes
  `findAll()`.
- [ACE-425 changes the same setters as ACE-441] → whichever lands second
  rebases, the setter bodies are small.
- [Removing the collection breaks a project that calls it] → `Breaking`
  entry with the core replacement, and the deprecation on `2` warns first.
- [The renames break third-party code naming the old namespace, property or
  constant] → each with a `Breaking` entry, as the maintainer agreed.
- [A finding provider of the upgrade check loads the persons settings] → the
  settings are read through the same service the TCA listener uses, and a
  settings file that cannot be read already fails there first.
