## 1. Tests first

- [ ] 1.1 Re-check the premise before anything else: confirm that
      `packages/fgtclb/academic-jobs/Resources/Private/Partials/Job/Contact.html`
      still writes the stored number into the link target unchanged, and that
      the file is still byte-identical on `main` and on `2`
      (`git diff main 2 -- <path>` prints nothing). Report it if either has
      moved.
- [ ] 1.2 `AcademicJobsListAndDetailPluginTest::detailPluginRendersContactInformationOfTheJob()`
      asserts `href="tel:+49 89 1234"` today and therefore pins the defect as
      intended behaviour. Correct that assertion to the dialable target and
      add one for the unchanged link text, then run the test against the
      unchanged partial and record that it fails.
- [ ] 1.3 Add the fixture
      `Tests/Functional/Plugins/Fixtures/AcademicJobsListAndDetailPlugin/jobPages_contactPhone.csv`
      with a second contact job whose number is stored without spaces, and a
      test asserting that its target is the number unchanged. A separate
      fixture rather than a row added to `jobPages.csv`, which several tests
      assert job counts and list contents against.
- [ ] 1.4 Check that `detailPluginOmitsContactBlockForJobWithoutContact()`
      already covers the third scenario of the spec (no phone, no link) and
      extend it only if it does not.

## 2. Implementation

- [ ] 2.1 Build the link target in `Job/Contact.html` with `f:replace`, in the
      shape `academic_persons` uses in `Profile/Contract/Field.html`, with the
      same short `f:comment` saying why the target and the label differ. Verify
      the tests of group 1 pass.
- [ ] 2.2 Revert the line on purpose, watch 1.2 and 1.3 go red, restore from a
      backup of the file and compare the md5 — `git checkout --` cannot restore
      an uncommitted state.

## 3. Documentation

- [ ] 3.1 Add
      `packages/fgtclb/academic-jobs/Documentation/Changelog/3.0/Important-JobContactPhoneLinkIsDialable.rst`
      from `Build/Documentation/Templates/Changelog-Important.rst`: the old and
      the new target, that the link text is unchanged, that an override of the
      partial keeps its own output, and that the fix is about spaces and not
      about validating what is stored. Check the reST over/underline lengths
      and verify with `checkRstRenderingAll`.
      The entry is required by
      `docs/workflow/changelog-and-documentation.md`, section *When a change
      needs a changelog entry*: the rendered output of an installation that
      changed nothing of its own is different after the update. "It is a
      bugfix, so an integrator has nothing to do" is **not** a reason to skip
      it (ACE-685).
- [ ] 3.2 No `docs/` page: the repository documentation describes the harness,
      the branch model and the architecture, and this change adds no concept to
      any of them. State that in the pull request.

## 4. Backport

- [ ] 4.1 Backport to branch `2`: a separate change on that branch after a
      backport analysis (`docs/workflow/backporting.md`), read its own
      `AGENTS.md` first. The partial is byte-identical, so the one-line patch
      is a literal copy; `f:replace` is already in use there in
      `academic_persons` and therefore proven on TYPO3 v12.
- [ ] 4.2 The **test harness is not** a literal copy. Branch `2`'s
      `jobPages.csv` carries no contact columns at all and the plugin test has
      no contact assertion, so the backport adds the columns, the fixture rows
      and the contact tests there rather than adapting existing ones. Budget
      for that; it is the larger half of the backport.
- [ ] 4.3 The changelog entry goes to `Documentation/Changelog/2.4/` on that
      branch. `3.0/` does not exist there and must not be created.

## 5. File the issue

- [ ] 5.1 After the implementation is green, file the ACE issue in YouTrack,
      summary prefix `[3.x][2.x]` and Version `2.4.0` because the backport is
      part of it, link it `relates to` ACE-679 (the same correction in
      `academic_persons`) and to ACE-685 (which recorded the defect), verify
      the key and rename the change to
      `ace-<NNN>-jobs-contact-tel-target`.
- [ ] 5.2 Commit as `[BUGFIX] ACE-<NNN>: Make the job contact phone dialable`
      in TYPO3 Core format, subject at most 52 characters, body wrapped at 72,
      no attribution of any kind.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` green for TYPO3 v13.
- [ ] 6.2 `composerUpdate`, then the same suites green for TYPO3 v14.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 The extension's `Documentation/` carries the changelog entry of 3.1;
      `README.md` and `CONTRIBUTING.md` still only summarise.
- [ ] 6.5 Anything left out is named in the pull request, with the reason.
- [ ] 6.6 Archive the change as the last commit of the same pull request.
