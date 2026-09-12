## Why

The content element of `academic_contacts4pages`
(`packages/fgtclb/academic-contact4pages`) always groups the contacts of a
page under role headings as soon as one contact has a role, and renders every
contact by calling the profile item partial of `academic_persons` directly.
Four of the six analysed projects replace the whole list template only to
drop the grouping and render their own card.

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
- The plugin provides the content element record to its templates, so an
  override that renders the core header partial works on TYPO3 v14.

The output is the same on TYPO3 v13 and v14. The record only matters on v14,
where the core header partial requires it.

## Capabilities

### New Capabilities

- `academic-contact4pages/page-contacts-list`: how the contacts content
  element groups and renders the contacts of its page.

### Modified Capabilities

None.

## Impact

- `academic_contacts4pages`: `Configuration/FlexForms/ContactsList.xml`, the
  TypoScript setup (default of the new option), `locallang_be.xlf` and its
  German file, `Resources/Private/Templates/Contacts/List.html`, a new
  `Resources/Private/Partials/Contacts/Item.html`, and the controller.
- Existing `List.html` overrides keep working; they do not get the option.
- No database change.

## Non-goals

- A layout select with several shipped card designs.
- Changing the page data processor; that is candidate `listings-19`.
- Backporting to branch `2`; to be decided after `main`.

Depends on candidate `listings-02` (change
`ace-tbd-contacts-skip-unresolved-profiles`), after which the item partial
never receives a contact without a visible profile.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-18`). Five of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-<slug>` when the issue is filed after implementation.
