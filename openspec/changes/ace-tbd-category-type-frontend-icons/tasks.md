## 1. Verify the premises

- [ ] 1.1 With `ace-tbd-frontend-icon-registry` merged, read its event, the
  order of event entries and `Configuration/FrontendIcons.php` entries, the
  answer for an unknown identifier and its testing helper trait. If any of
  them differs from `design.md` (Context), stop and update this change.
- [ ] 1.2 On `main`, in a functional test on both core versions: render a
  type declared without `icon` through `core:icon` and confirm the exception
  1440754980, and declare a group `group` with a type `x` next to a group `x`
  with an icon and confirm that `category_types.group.x` resolves to only one
  of the two files. If either does not hold, correct `design.md`.

## 2. Models and loader

- [ ] 2.1 `CategoryType`: append `frontendIcon` and `?bool
  frontendInlineIcon` to the constructor, carry them through `fromArray()`,
  `toArray()`, `__set_state()` and `jsonSerialize()`, add
  `getFrontendIcon()` and `isFrontendInlineIcon()` with the fallback of the
  spec, update the array shape of `CategoryTypeRegistry::toArray()`. Unit
  tests in `CategoryTypeTest` for every row of the requirement "Inlining
  follows the file it is declared for" and for an old cache shape without
  the two keys. Shown to fail by letting `isFrontendInlineIcon()` return
  `inlineIcon` unconditionally.
- [ ] 2.2 `CategoryTypeGroup`: the same two properties with setters and
  resolving getters, `getIconIdentifier()` returning
  `category_types_group.<identifier>`. `CategoryTypeGroupTest:110` and the
  fallback cases, shown to fail on the old identifier.
- [ ] 2.3 `CategoryTypeLoader`: before the `useExisting` merge, a named
  `frontendIcon` without `frontendInlineIcon` sets it to `null`, and the
  group merge does the same for a non-empty `frontendIcon`. The rule for
  `icon` and `inlineIcon` stays. New unit fixtures `frontend_icon_override`,
  `frontend_flag_override` and `group_frontend_icon_override`, one test per
  override scenario of both specs, each shown to fail by removing the reset.
  The existing override tests (`CategoryTypeLoaderTest.php:194-206`) stay
  green unchanged.

## 3. Registration

- [ ] 3.1 Add the `final readonly` resolver `Imaging\CategoryTypeIconProviderResolver`
  and use it in the `BootCompletedEvent` closure. `CategoryTypeIconsTest`
  stays green apart from its three group constants, which take the new
  identifier.
- [ ] 3.2 Add the `final` listener `EventListener\AddCategoryTypeFrontendIcons`
  with TYPO3's `#[AsEventListener]` on the collection event of
  `academic_base`: one entry per type and per group with a frontend file,
  none for an empty one. Mark both new classes `@internal`.

## 4. Functional tests on both core versions

- [ ] 4.1 Fixture extension `test_category_types_frontend_icons`
  (`tests/category-types-frontend-icons`): a type per frontend case of the
  spec, a bitmap, a type without a file, a group with `frontendIcon`, the
  group `group` with a type `x` next to a group `x`, and a
  `Configuration/FrontendIcons.php` replacing one type icon.
- [ ] 4.2 `Tests/Functional/Imaging/CategoryTypeFrontendIconsTest`: each
  scenario of `category-type-icons` against both registries (provider,
  source, markup), the group collision, the site package replacement and the
  type without a file rendering the not-found drawing. Show on v13 and on
  v14 that removing the listener's attribute turns the frontend assertions
  red, that a `getFrontendIcon()` returning `icon` turns the dedicated file
  cases red, that dropping the empty check turns the no-file case red, and
  that removing the `FrontendIcons.php` entry turns the replacement case red.
- [ ] 4.3 `CategoryTypesTest` of `academic-partners`, `academic-programs` and
  `academic-projects` (`:54`): the group icon under the new identifier in
  both registries. Their `RecordIconsTest` or a new test: every shipped type
  icon in the frontend registry with the current colour provider, through
  the trait of the registry change. Shown to fail by removing the listener's
  attribute.

## 5. Documentation

- [ ] 5.1 `Documentation/Developers/Icons/Index.rst` of `category_types`:
  both registries, the frontend example with `<ab:icon>`, `frontendIcon`,
  `frontendInlineIcon` and the flag rule, the frontend reset on override, replacing an
  icon in `FrontendIcons.php`, the group identifier and why the `group` rule
  is gone.
- [ ] 5.2 `Documentation/Developers/CategoryTypes/Index.rst`: the type and
  group keys, "Changing only the icon" (unchanged rule for `icon`, the new
  rule for `frontendIcon`), a frontend only
  replacement, the getter list.
- [ ] 5.3 `Feature-CategoryTypeFrontendIcons.rst` in
  `Documentation/Changelog/3.0/` of `category_types`, with what an
  integrator does. Amend the unreleased
  `Feature-CategoryTypeIconsCanBeInlined.rst` (the frontend pair) and
  `Feature-CategoryTypeGroupTitlesAndIcons.rst` (identifier, no `group`
  rule), `academic-partners` `Feature-CategoryTypeGroupTitle.rst`,
  `academic-programs` and `academic-projects`
  `Important-CategoryTypeGroupTitleAndIcon.rst`.
- [ ] 5.4 `docs/architecture/icons.md` (programmatic registration, group
  icons, provider choice), `docs/testing/fixture-extensions.md` (row and
  measured count), and the measured `#[AsEventListener]` counts in
  `docs/architecture/dependency-injection.md` and `AGENTS.md`. Grep `docs/`
  for `category_types.group` and for "named `group`" and fix every hit.

## 6. File the issue

- [ ] 6.1 After implementation, file the ACE issue (subtask of ACE-10,
  relates to the umbrella and to the registry change) and verify the key
  with a GET request.
- [ ] 6.2 Rename the change to `ace-<NNN>-category-type-frontend-icons` and
  verify `openspec validate --strict` passes under the new name.

## 7. Definition of done

- [ ] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (`-j auto`) green.
- [ ] 7.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (`-j auto`) green.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` is updated, and `README.md` and `CONTRIBUTING.md` still only
  summarize.
- [ ] 7.5 Commit as `[FEATURE] ACE-<NNN>: Frontend icons for category types`
  in TYPO3 Core format, and archive the change as the last commit of the
  pull request.
