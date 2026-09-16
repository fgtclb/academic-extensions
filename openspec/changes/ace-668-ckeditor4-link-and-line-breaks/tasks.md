## 1. Record the behaviour before the change

- [x] 1.1 Add a functional test in `academic_persons_edit` that submits a link
  in a rich text field through the real edit form and asserts the stored value,
  and that the stored link is rendered back into the form. Recorded green on the
  unchanged code: the storing path was never the defect, the missing button was.
- [x] 1.2 Add a functional test in `academic_persons` that renders the profile
  detail view for a profile whose rich text fields hold an `https`, a
  `javascript:` and a `data:` link, and asserts that only the first is rendered.
  Recorded green on the unchanged code on TYPO3 v12, so the link button cannot
  become a way to publish a dangerous URI.
- [x] 1.3 Probe what a backend save does to such a value. A DataHandler save
  stores it unchanged, so no TCA or rich text preset change is needed. The probe
  was removed again; its result is recorded in `design.md`.

## 2. The editor configuration

- [x] 2.1 Add the `links` toolbar group, remove the `Anchor` button and switch
  off the advanced tab of the link dialog.
- [x] 2.2 Take the editor language from the primary subtag of the language the
  document declares, falling back to English.
- [x] 2.3 Set the writer rules of `p`, `ul`, `ol`, `li` and `h2` to `h6` on
  `instanceReady`, so each block element is written on its own line.
- [x] 2.4 Rebuild the committed artifact with `buildJs` and keep
  `checkJsBuildClean` green.

## 3. Documentation

- [x] 3.1 Add `Documentation/Changelog/2.4/Important-FrontendRichTextEditorOffersLinks.rst`
  to `academic_persons_edit`.
- [x] 3.2 No `docs/` change: the repository's `docs/` describes the asset build
  and the development environment, and a CKEditor toolbar configuration is not a
  concept it carries. Stated here rather than left unexplained.

## 4. Verify by hand what no suite can assert

- [ ] 4.1 This branch has no `testJs`, so the toolbar itself is checked in the
  v12 and v13 development instances: the link button appears, adds and removes a
  link, the anchor button is absent, and the stored value carries one block
  element per line. Record the result in the pull request.

## 5. Definition of done

- [x] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v12 and for v13, each after its own `composerUpdate`.
- [x] 5.2 `buildJs`, `typecheckJs`, `lintTypescript` and
  `checkRstRenderingSingle academic-persons-edit` green;
  `checkJsBuildClean` green once the rebuilt artifact is committed.
- [x] 5.3 The extension's `Documentation/Changelog/2.4/` entry written.
- [ ] 5.4 Archive the change as the last commit of the pull request.
