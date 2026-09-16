## Why

The frontend profile editor of `academic_persons_edit`
(`packages/fgtclb/academic-persons-edit`) attaches CKEditor 4 to the rich text
fields of the profile form. Its configuration offers no link button, fixes the
editor's own interface language to English, and writes the markup of a whole
field on a single line.

A person maintaining their own profile therefore cannot add a link at all, and
the stored value is unreadable wherever the raw markup is shown — a database
dump, a diff, the backend text field. Two of the six analysed installations ship
their own copy of the editor configuration to get both.

`main` is not affected and carries the decision as a record only: it replaced
CKEditor 4 with CKEditor 5, which has the link item and a server side sanitiser
allowing `a[href]`.

## What Changes

- The toolbar of the frontend rich text editor offers a link group, so a link
  can be added and removed. The anchor button is not offered, and the advanced
  tab of the link dialog is switched off — both set values the public views do
  not render.
- The editor's interface language follows the language the document declares
  instead of always being English.
- Block elements are written one per line, so a field holding several
  paragraphs or a list is readable as stored.

The behaviour is identical on TYPO3 v12 and v13 — the configuration is client
side and does not depend on the core version.

## Capabilities

### New Capabilities

- `academic-persons-edit/profile-rich-text-editing`: what the rich text fields
  of the frontend profile editor offer, and what a stored value looks like.

### Modified Capabilities

None.

## Impact

- `academic_persons_edit`: the TypeScript source of the editor configuration and
  its committed build `Resources/Public/JavaScript/frontend/ckeditor.js`.
- Functional tests of the storing and the rendering of a link, in
  `academic_persons_edit` and in `academic_persons`.
- A 2.4 changelog entry of `academic_persons_edit`.

## Non-goals

- Replacing CKEditor 4 on this branch.
- A server side sanitiser for the submitted value. The rendering already refuses
  the dangerous protocols, which is pinned by a test; an input sanitiser is a
  separate security change if one is wanted.
- The backend rich text presets. The five profile fields declare
  `richtextConfiguration: profile`, whose preset allows no `a` tag and offers no
  link button. That mismatch is pre-existing, identical on `main`, and left as
  it is.

## Source

Backport target of the change recorded on `main` as
`ace-668-ckeditor4-link-and-line-breaks`, which carries the decision and no
code. Derived from the project differences analysis of 2026-09-12, candidate
`persons-data-18`. Filed as ACE-668.
