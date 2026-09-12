## 1. Settings

- [ ] 1.1 Add `Configuration/Sets/Full/settings.definitions.yaml` with
      `plugin.tx_academicprograms.facts.fields` (empty) and
      `plugin.tx_academicprograms.card.fields` (`degree`), mirror the
      defaults in `constants.typoscript`, and map them into the plugin
      settings; a functional site set test reads both values back.

## 2. Builder and rendering

- [ ] 2.1 Add the stateless `ProgramFactsBuilder` and the `ProgramFact`
      item with unit tests for: configured order, unknown identifier
      skipped, zero credit points omitted, empty list per place. Break the
      ordering on purpose and watch the order test fail.
- [ ] 2.2 After pull request #617 (ACE-591) has merged, add the Font Awesome
      Free solid credit points SVG under `Icons/info/` and register it as
      `tx-academicprograms-info-credit-points`; the icon registry test
      covers the identifier and fails before the registration.
- [ ] 2.3 Add `factsFields` to the `program-data` processor and render
      `Program/Facts` in `Pages/AcademicProgram.html` (its
      `Program/Page/Facts` section once the layout change landed); a
      functional page test with `facts.fields = creditPoints,degree` asserts
      credit points before the degree and no location (today location is
      present, credit points come last). Repeat the test with a PAGEVIEW
      fixture site package.
- [ ] 2.4 Render `Program/Facts` in `Templates/Details/Show.html`; a
      functional plugin test with the same setting asserts the order (today
      no credit points).
- [ ] 2.5 Render the card facts in `Partials/Program/Item.html`; a
      functional list test with `card.fields = degree,standard_period`
      asserts both in order, and one without configuration asserts the degree
      only.
- [ ] 2.6 Functional tests for the empty setting assert the output of page
      and details element is unchanged against today's fixtures.
- [ ] 2.7 Functional test that the category type facts follow the category
      type order, on the program page and in the details element with an
      empty setting: a fixture extension gives `location` and `degree`
      swapped priorities (location above degree) and the test asserts the
      location before the degree. Show it red first by having the builder
      iterate the types in an order of its own, then restore. If
      `ace-tbd-category-type-priority-order` has not landed yet, the test
      pins the current registry order (degree before location, no fixture
      priorities) and that change flips it to priorities (its task 2.4).
      A second case with `facts.fields = location,degree` asserts the list
      order holds regardless of the type order.

## 3. Remove the categories partial

- [ ] 3.1 Add a functional test with a fixture partial root that overrides
      `Program/Categories` with a marker: assert that neither the program
      page nor the details element renders the marker. Record that it fails
      before 2.3 and 2.4.
- [ ] 3.2 Delete `Resources/Private/Partials/Program/Categories.html`; grep
      that no template, test or documentation of the monorepo references
      `Program/Categories` any more, except changelog entries of earlier
      versions. Adjust the reference in
      `Documentation/Changelog/3.0/Breaking-RecordAndCategoryIconsFollowTheColourScheme.rst`
      so it no longer names the removed partial as one to follow.

## 4. Documentation

- [ ] 4.1 Document both settings, the vocabulary and the per-place defaults
      in `academic_programs` `Documentation/` and in `docs/`, linked from the
      section `Index.md`.
- [ ] 4.2 Add `Documentation/Changelog/3.0/Feature-ProgramFactsFieldList.rst`
      from `Build/Documentation/Templates/`; check the reST line lengths.
- [ ] 4.3 Add
      `Documentation/Changelog/3.0/Breaking-ProgramCategoriesPartialRemoved.rst`
      from `Build/Documentation/Templates/Changelog-Breaking.rst`: the removed
      partial, that an override of it is silently no longer rendered, and
      the migration to `Program/Facts/Item` or `Program/Facts` plus
      `plugin.tx_academicprograms.facts.fields`. Check the reST
      over/underline lengths.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
      change to `ace-<NNN>-program-facts-field-list`, and commit in TYPO3
      Core format `[!!!][FEATURE] ACE-<NNN>: <subject>`.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` green for TYPO3 v13; the same after its own
      `composerUpdate` for v14.
- [ ] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.3 `docs/` and the `Documentation/` changelog updated; `README.md`
      and `CONTRIBUTING.md` only link.
- [ ] 6.4 Archive the change as the last commit of the pull request.
