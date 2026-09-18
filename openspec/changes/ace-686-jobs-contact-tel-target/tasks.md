## 1. Tests first

- [x] 1.1 Premise re-checked at `b917ac557` before anything else:
      `packages/fgtclb/academic-jobs/Resources/Private/Partials/Job/Contact.html`
      still wrote the stored number into the link target unchanged, the file was
      still byte-identical on `main` and on `origin/2`
      (`git diff main origin/2 -- <path>` printed nothing), and
      `Templates/Job/Show.html:49` was still its only call site. Nothing had
      moved.
- [x] 1.2 `AcademicJobsListAndDetailPluginTest::detailPluginRendersContactInformationOfTheJob()`
      asserted `href="tel:+49 89 1234"` and therefore pinned the defect as
      intended behaviour. The assertion now reads
      `href="tel:+49891234">+49 89 1234</a>`, which pins target and label in one
      contiguous string. Run against the unchanged partial - md5 equal to the
      committed file - it failed, and it is the **only** assertion of this
      change that can fail without the fix.
- [x] 1.3 Added the fixture
      `Tests/Functional/Plugins/Fixtures/AcademicJobsListAndDetailPlugin/jobPages_contactPhone.csv`
      with a contact job whose number is stored without spaces, and
      `detailPluginRendersAContactPhoneStoredWithoutSpacesUnchanged()`. A
      separate fixture rather than a row added to `jobPages.csv`, which several
      tests assert job counts and list contents against. This test is a
      **regression guard, not a proof**: with no spaces to strip, the fixed and
      the unfixed partial render the same target, so it is green either way.
- [x] 1.4 `detailPluginOmitsContactBlockForJobWithoutContact()` does **not**
      cover the third scenario of the spec: its job carries no contact at all,
      so no block renders, while the spec means a contact that has a name and an
      e-mail and no phone. Added
      `detailPluginRendersNoPhoneLinkForAContactWithoutAPhoneNumber()` and a
      second job to the new fixture for it. Also a guard, green either way.

## 2. Implementation

- [x] 2.1 The link target is built with `f:replace` in `Job/Contact.html`, in
      the shape `academic_persons` uses in `Profile/Contract/Field.html`, with
      the same short `f:comment` saying why the target and the label differ. The
      value is passed unquoted because nothing is concatenated onto it - the
      persons partials wrap theirs in `'{...}'` only to prepend `telPrefix`. The
      tests of group 1 pass.
- [x] 2.2 The proof is the red run of 1.2 recorded there, which ran against the
      committed state of the partial (md5 compared against
      `git show HEAD:<path>`) rather than against a hand-reverted copy. A second
      revert cycle would repeat that run, not add to it. Note for the pull
      request: three tests touch the target, one of them proves the fix.

## 3. Documentation

- [x] 3.1 Added
      `packages/fgtclb/academic-jobs/Documentation/Changelog/3.0/Important-JobContactPhoneLinkIsDialable.rst`:
      the old and the new target, the unchanged link text, that an override of
      the partial keeps its own output, and that the fix is about ordinary
      spaces and not about validating what is stored - a slash or a non-breaking
      space was undialable before and stays so. Over/underlines checked,
      `checkRstRenderingAll` green.
      The entry is required by
      `docs/workflow/changelog-and-documentation.md`, section *When a change
      needs a changelog entry*: the rendered output of an installation that
      changed nothing of its own is different after the update. "It is a
      bugfix, so an integrator has nothing to do" is **not** a reason to skip
      it (ACE-685).
- [x] 3.2 No `docs/` page: the repository documentation describes the harness,
      the branch model and the architecture, and this change adds no concept to
      any of them. Stated in the pull request.

## 4. Backport

- [x] 4.1 Backported to branch `2` as its own change after a backport analysis
      (`docs/workflow/backporting.md`), with that branch's own `AGENTS.md` read
      first. The partial was byte-identical, so the patch is a literal copy -
      verified by diffing the two patched files against each other.
      `f:replace` was already in use there in `academic_persons` and is
      therefore proven on TYPO3 v12.
- [x] 4.2 The test harness was **not** a copy. Branch `2`'s `jobPages.csv`
      carries no contact columns at all and its plugin test had no contact
      assertion, so the backport adds a contact fixture and all three tests
      there, and parameterises that class's `setUpTestCase()` - which hardcoded
      one fixture - the way this branch's copy already does. That was the larger
      half of the backport, as budgeted.
- [x] 4.3 The changelog entry went to `Documentation/Changelog/2.4/` on that
      branch. `3.0/` does not exist there and was not created.

## 5. File the issue

- [x] 5.1 Filed as **ACE-686** (Bug, Version 2.4.0, State Open, assignee
      s.buerk), summary prefix `[3.x][2.x]` because the backport is part of it,
      linked `relates to` ACE-679 (the same correction in `academic_persons`)
      and ACE-685 (which planned this change). Key verified, change renamed to
      `ace-686-jobs-contact-tel-target`.
- [x] 5.2 Committed as `[BUGFIX] ACE-686: Make the contact phone dialable` in
      TYPO3 Core format, subject within 52 characters, body wrapped at 72, no
      attribution of any kind.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` green for TYPO3 v13.
- [x] 6.2 `composerUpdate`, then the same suites green for TYPO3 v14.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 The extension's `Documentation/` carries the changelog entry of 3.1;
      `README.md` and `CONTRIBUTING.md` still only summarise.
- [x] 6.5 Anything left out is named in the pull request, with the reason.
- [x] 6.6 Archived as the last commit of the same pull request.
