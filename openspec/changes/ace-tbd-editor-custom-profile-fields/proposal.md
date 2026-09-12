## Why

Projects add their own profile columns (a name prefix, initials, eight fields
in one project) and want people to edit them in the frontend editor. On 3.0
the editor only knows the fixed properties of its form data object, and the
persons settings say they "do not create database columns, domain properties
or new renderers". The 2.x way of doing it, XCLASSing the form data object
and the factory, is fatal on 3.0.

## What Changes

- A profile field in the persons settings of `academic_persons`
  (`packages/fgtclb/academic-persons`) can be declared as a project field:
  `custom: true` plus the database column, the field type, the renderer and
  the validators.
- The frontend editor of `academic_persons_edit`
  (`packages/fgtclb/academic-persons-edit`) renders such a field with the
  configured renderer, validates, limits and sanitises it like any other
  field, reads it from the stored profile row and writes it to that column.
- The project ships the column itself (SQL and TCA); a declared column that is
  not in the profile TCA fails the settings build with a message naming it.
- On a translation the value is read from and written to the translation row.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons-edit/project-profile-fields`: how an integrator makes a
  project column of the profile editable in the frontend editor.

### Modified Capabilities

None.

## Impact

- `academic_persons`: the settings graph accepts and checks `custom` fields;
  the `Settings.yaml` header comment changes.
- `academic_persons_edit`: form data carries a bag of project values; the
  profile update writes them after the regular save.
- A fixture extension with a project column for the functional tests.
- Integrator documentation and a 3.0 feature changelog.

## Non-goals

- Creating columns, TCA or domain model properties.
- Project fields on contracts, contacts or documents.
- Showing project fields on the public profile (`persons-display` family).
- Select fields with options; the first version supports the scalar
  renderers.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-14`). Two of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-editor-custom-profile-fields` when the issue is filed after
implementation.
