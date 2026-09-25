## Why

The backport of the `main` change of the same name, archived there as
`openspec/changes/archive/2026-09-25-ace-728-contacts-group-by-role-item-partial`
in its own pull request.

The content element of `academic_contacts4pages`
(`packages/fgtclb/academic-contact4pages`) always groups the contacts of a
page under role headings as soon as one contact has a role, and renders every
contact by calling the profile item partial of `academic_persons` directly.
Four of the six projects analysed for the `main` change replace the whole
list template only to drop the grouping and render their own card.

## What Changes

- A new content element option "Group by role", on by default, which is
  today's output. Content elements saved before the option existed keep
  grouping.
- With the option off, all contacts of the page render in one list in the
  order the editor sorted them, and each contact shows its role name when it
  has one.
- Every contact, grouped or not, renders through one new item partial of the
  extension. By default it renders the profile item of `academic_persons`
  exactly as today, so a project overrides one small partial for its card
  instead of the list template.

The output is the same on TYPO3 v12 and v13.

Not backported: the `record` view variable the `main` change assigns. It
exists for the header partial of TYPO3 v14, which this branch does not
support, and `academic_base` here has no trait to build it.

## Capabilities

### New Capabilities

- `academic-contact4pages/page-contacts-list`: how the contacts content
  element groups and renders the contacts of its page.

### Modified Capabilities

None.

## Impact

- `academic_contacts4pages`: `Configuration/FlexForms/ContactsList.xml`, the
  TypoScript setup (default of the new option), `locallang_be.xlf` and its
  German file, `Resources/Private/Templates/Contacts/List.html` and a new
  `Resources/Private/Partials/Contacts/Item.html`. No PHP change.
- Existing `List.html` overrides keep working; they do not get the option.
- No database change.

## Non-goals

- A layout select with several shipped card designs.
- Changing the page data processor.
