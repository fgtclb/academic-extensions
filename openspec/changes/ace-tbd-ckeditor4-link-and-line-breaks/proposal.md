## Why

Branch `2` only; this change on main records the decision and carries
`skip_specs: true`; the implementation is a separate change on branch 2.

On branch `2` the frontend editor of `academic_persons_edit`
(`packages/fgtclb/academic-persons-edit`) configures CKEditor 4 without a link
button, with the editor language fixed to English, and without writer rules, so
the stored HTML runs block elements together on one line. Two projects on 2.3.x
ship their own copy of the editor configuration to get links and readable line
breaks.

`main` is not affected: it replaced CKEditor 4 with CKEditor 5, whose
toolbar has the `link` item with the protocols http, https, mailto and tel
(`Resources/Private/TypeScript/frontend/profile/rich-text.ts`), follows the
site language, and sanitises every rich text value on the server with an
allow list that keeps `a[href]`.

## What Changes

On `main`: nothing but this record.

On branch `2`, as its own change after a backport analysis:

- Add the `links` toolbar group to the CKEditor 4 configuration
  (`Resources/Private/TypeScript/frontend/ckeditor.ts`), without the anchor
  button.
- Take the editor language from the document language instead of `en`.
- Set writer rules for block elements when an instance is ready, so the
  stored HTML has one block per line.
- Verify that a link survives saving and the public rendering on TYPO3 v12
  and v13.

## Capabilities

### New Capabilities

None; `skip_specs: true`. Specs are branch scoped, and the behaviour exists on
branch `2` only.

### Modified Capabilities

None.

## Impact

- `main`: this change directory only.
- Branch `2`: the TypeScript source, its committed build
  `Resources/Public/JavaScript/frontend/ckeditor.js`, and a 2.4 changelog
  entry of `academic_persons_edit`.

## Non-goals

- Replacing CKEditor 4 on branch `2`.
- A server-side href check or sanitiser on branch `2`. If one is wanted, it
  is a separate security change.
- Any change on `main`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-18`). Two of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-ckeditor4-link-and-line-breaks` when the issue is filed after
implementation.
