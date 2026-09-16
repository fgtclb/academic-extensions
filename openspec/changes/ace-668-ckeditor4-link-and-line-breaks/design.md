## Context

See `proposal.md`. Verified on `origin/2` (`a5a3d21a1`, 2026-09-11):

- `Resources/Private/Templates/Profile/Edit.html` loads CKEditor 4.22.1
  `standard` from the CDN and the configuration module
  `Resources/Public/JavaScript/frontend/ckeditor.js`, built from
  `Resources/Private/TypeScript/frontend/ckeditor.ts` (ACE-394).
- The configuration has `language: 'en'` and the toolbar groups
  `basicstyles`, `paragraph/list` and `clipboard/cleanup`; no `links`.
  The `standard` build ships the link plugin, so only the group is missing.
- The persons_edit sources on branch `2` contain no server-side HTML
  sanitiser; the edit view renders `bodytext` through `f:format.html()`.
- Branch `2`'s `runTests.sh` has seven node suites and no `testJs`, so the
  JavaScript test the analysis proposed does not exist there.

On `main` the editor is CKEditor 5 (`rich-text.ts`), with the link item and a
server-side sanitiser; nothing is to do.

## Goals / Non-Goals

**Goals:**

- Let the two projects drop their copied configuration.

**Non-Goals:**

- Introducing `testJs` on branch `2` for one configuration object.

## Decisions

### A record on main, the implementation on branch 2

Specs are branch scoped and never synced between `main` and `2`
(`docs/workflow/backporting.md`), so the behaviour cannot be specified on
`main`. The change on `main` keeps the decision visible next to the other
candidates of the analysis and is archived with it.

Rejected: no change on `main` at all. The candidate would then vanish from the
set this analysis produced.

### Configuration changes on branch 2

`toolbarGroups` gets `{ name: 'links', groups: ['links'] }`, `removeButtons`
gets `Anchor`. `language` is `document.documentElement.lang` cut to the
primary subtag, with `en` as fallback. On `instanceReady`, the writer rules of
`p`, `ul`, `ol`, `li`, `h2` to `h6` get `breakAfterOpen: false`,
`breakBeforeClose: false`, `breakAfterClose: true`, `indent: false`.

Rejected: shipping the projects' copies. They differ from each other and from
the upstream sources.

Guessed layout — a sketch, not a design:

```text
[B] [I] | [bullet list] [numbered list] | [link] [unlink] | [clean]
```

### Decided: no server-side check in this bugfix

The branch `2` change adds no server-side href check. A functional test pins
that a `javascript:` or `data:` href is not rendered, on TYPO3 v12 and v13.
The link button adds no input capability, since a request can already carry
any markup today. `bodytext` is rendered through `f:format.html()`, whose
parseFunc sanitises by default on v13 and lets only http, https, mailto, tel,
same-host and `t3://` hrefs pass. The v12 path and the branch `2` templates
were not re-read for this decision, so the pinning test confirms it on both
versions. A branch `2` input sanitiser, if wanted, is a separate security
change.

## Risks / Trade-offs

- [Without a sanitiser a link carries any protocol, `javascript:` included]
  → Set `linkShowAdvancedTab: false`, restrict the protocol drop-down, and
  pin with a functional test that such an href is not rendered. If the test
  shows it is, this change does not grow: the finding is reported privately
  to the TYPO3 Security Team, not in a public issue or pull request.
- [No automated JavaScript test on branch `2`] → A functional test saves and
  renders a body with `<a href>`; the editor itself is checked by hand in both
  development instances and the result recorded in the pull request.

## Open Questions

None.
