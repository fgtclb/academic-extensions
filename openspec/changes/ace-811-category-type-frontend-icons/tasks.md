## 1. Verify the premises

- [x] 1.1 With `ace-810-frontend-icon-registry` merged, read its event, the
  order of event entries and `Configuration/FrontendIcons.php` entries, the
  answer for an unknown identifier and its testing helper trait. If any of
  them differs from `design.md` (Context), stop and update this change.
- [x] 1.2 On `main`, in a functional test on both core versions: render a
  type declared without `icon` through `core:icon` and confirm the exception
  1440754980, and declare a group `group` with a type `x` next to a group `x`
  with an icon and confirm that `category_types.group.x` resolves to only one
  of the two files. If either does not hold, correct `design.md`.
  Both hold on 13.4.35 and 14.3.7, with the `main` classes and the fixture of
  task 4.1: the icon factory throws 1440754980 for the type without a file,
  and `category_types.group.collision` carries the file of the group
  `collision`, the type's file is lost.

## 2. Models and loader

- [x] 2.1 `CategoryType`: append `frontendIcon` and `?bool
  frontendInlineIcon` to the constructor, carry them through `fromArray()`,
  `toArray()`, `__set_state()` and `jsonSerialize()`, add
  `getFrontendIcon()` and `isFrontendInlineIcon()` with the fallback of the
  spec, update the array shape of `CategoryTypeRegistry::toArray()`. Unit
  tests in `CategoryTypeTest` for every row of the requirement "Inlining
  follows the file it is declared for" and for an old cache shape without
  the two keys. Shown to fail by letting `isFrontendInlineIcon()` return
  `inlineIcon` unconditionally.
- [x] 2.2 `CategoryTypeGroup`: the same two properties with setters and
  resolving getters, `getIconIdentifier()` returning
  `category_types_group.<identifier>`. `CategoryTypeGroupTest:110` and the
  fallback cases, shown to fail on the old identifier.
- [x] 2.3 `CategoryTypeLoader`: before the `useExisting` merge, a named
  `frontendIcon` without `frontendInlineIcon` sets it to `null`, and the
  group merge does the same for a non-empty `frontendIcon`. The rule for
  `icon` and `inlineIcon` stays. New unit fixtures `frontend_icon_override`,
  `frontend_flag_override` and `group_frontend_icon_override`, one test per
  override scenario of both specs, each shown to fail by removing the reset.
  The existing override tests (`CategoryTypeLoaderTest.php:194-206`) stay
  green unchanged.
  Done differently: the existing override tests needed the two new keys in
  their whole-array assertions, as did `CategoryTypeRegistryTest` and the
  `DefaultExtensionCategoryTypes.php` fixtures of partners, programs and
  projects. The base declarations are a fourth unit fixture,
  `frontend_types`. A YAML `frontendInlineIcon: ~` reads as not declared for
  types and groups alike, pinned by a fifth fixture, `frontend_flag_cleared`.

## 3. Registration

- [x] 3.1 Add the `final readonly` resolver `Imaging\CategoryTypeIconProviderResolver`
  and use it in the `BootCompletedEvent` closure. `CategoryTypeIconsTest`
  stays green apart from its three group constants, which take the new
  identifier.
- [x] 3.2 Add the `final` listener `EventListener\AddCategoryTypeFrontendIcons`
  with TYPO3's `#[AsEventListener]` on the collection event of
  `academic_base`: one entry per type and per group with a frontend file,
  none for an empty one. Mark both new classes `@internal`.
  The listener identifier is `category-types/add-category-type-frontend-icons`.
  The resolver is public in the container, because the `BootCompletedEvent`
  closure takes it from there.

## 4. Functional tests on both core versions

- [x] 4.1 Fixture extension `test_category_types_frontend_icons`
  (`tests/category-types-frontend-icons`): a type per frontend case of the
  spec, a bitmap, a type without a file, a group with `frontendIcon`, the
  group `group` with a type `x` next to a group `x`, and a
  `Configuration/FrontendIcons.php` replacing one type icon.
  Done differently: the colliding pair is the type `collision` of the group
  `group` next to the group `collision`, because a type identifier has to be
  unique across groups and `x` reads poorly in a test. The fixture needs no
  loading order, the collect event is applied before every
  `Configuration/FrontendIcons.php`.
- [x] 4.2 `Tests/Functional/Imaging/CategoryTypeFrontendIconsTest`: each
  scenario of `category-type-icons` against both registries (provider,
  source, markup), the group collision, the site package replacement and the
  type without a file rendering the not-found drawing. Show on v13 and on
  v14 that removing the listener's attribute turns the frontend assertions
  red, that a `getFrontendIcon()` returning `icon` turns the dedicated file
  cases red, that dropping the empty check turns the no-file case red, and
  that removing the `FrontendIcons.php` entry turns the replacement case red.
- [x] 4.3 `CategoryTypesTest` of `academic-partners`, `academic-programs` and
  `academic-projects` (`:54`): the group icon under the new identifier in
  both registries. Their `RecordIconsTest` or a new test: every shipped type
  icon in the frontend registry with the current colour provider, through
  the trait of the registry change. Shown to fail by removing the listener's
  attribute.
  The new test method sits in each `RecordIconsTest`,
  `categoryTypeIconIsTheSameFrontendIcon()`, with a data provider filtered from
  the existing identifier list.

## 5. Documentation

- [x] 5.1 `Documentation/Developers/Icons/Index.rst` of `category_types`:
  both registries, the frontend example with `<ab:icon>`, `frontendIcon`,
  `frontendInlineIcon` and the flag rule, the frontend reset on override, replacing an
  icon in `FrontendIcons.php`, the group identifier and why the `group` rule
  is gone.
- [x] 5.2 `Documentation/Developers/CategoryTypes/Index.rst`: the type and
  group keys, "Changing only the icon" (unchanged rule for `icon`, the new
  rule for `frontendIcon`), a frontend only
  replacement, the getter list.
- [x] 5.3 `Feature-CategoryTypeFrontendIcons.rst` in
  `Documentation/Changelog/3.0/` of `category_types`, with what an
  integrator does. Amend the unreleased
  `Feature-CategoryTypeIconsCanBeInlined.rst` (the frontend pair) and
  `Feature-CategoryTypeGroupTitlesAndIcons.rst` (identifier, no `group`
  rule), `academic-partners` `Feature-CategoryTypeGroupTitle.rst`,
  `academic-programs` and `academic-projects`
  `Important-CategoryTypeGroupTitleAndIcon.rst`.
- [x] 5.4 `docs/architecture/icons.md` (programmatic registration, group
  icons, provider choice), `docs/testing/fixture-extensions.md` (row and
  measured count), and the measured `#[AsEventListener]` counts in
  `docs/architecture/dependency-injection.md` and `AGENTS.md`. Grep `docs/`
  for `category_types.group` and for "named `group`" and fix every hit.
  Also brought to the measured values: the counts at the top of
  `docs/architecture/class-design.md` and its table of `final` classes, and the
  number of listener classes in `docs/architecture/dependency-injection.md`,
  which were stale since the registry change.

## 6. File the issue

- [x] 6.1 After implementation, file the ACE issue (subtask of ACE-10,
  relates to the umbrella and to the registry change) and verify the key
  with a GET request.
  Done differently: filed as ACE-811 before the implementation, so the issue
  could follow the work, as the request asked.
- [x] 6.2 Rename the change to `ace-<NNN>-category-type-frontend-icons` and
  verify `openspec validate --strict` passes under the new name.

## 7. Definition of done

- [x] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (`-j auto`) green.
- [x] 7.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (`-j auto`) green.
- [x] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 7.4 `docs/` is updated, and `README.md` and `CONTRIBUTING.md` still only
  summarize.
- [ ] 7.5 Commit as `[FEATURE] ACE-<NNN>: Frontend icons for category types`
  in TYPO3 Core format, and archive the change as the last commit of the
  pull request.
