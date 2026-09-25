## Context

The files the `main` change touched were compared with this branch first.
`List.html`, `ContactsList.xml` and `ContactsController.php` are identical on
both branches, so the template and FlexForm changes carry over unchanged. The
differences:

- `Configuration/TypoScript/List/setup.typoscript` here maps only `detailPid`
  and registers no `academic_base` partial path; the new default goes next to
  `detailPid`.
- The XLIFF files here are indented with tabs; the new units follow them.
- `academic_base` here has no `GetCurrentContentRecordMethodTrait`, and the
  `record` variable only matters for the TYPO3 v14 header partial, so the
  controller stays untouched.
- The plugin test class here is the small one of ACE-101, and there is no
  `PluginFlexFormTest` nor its testing-helper trait.

## Goals / Non-Goals

**Goals:**

- The grouping becomes an option without changing today's default output, on
  TYPO3 v12 and v13.
- A project changes the card by overriding one partial.

**Non-Goals:**

- The `record` view variable (see Context).
- Moving the grid wrapper into the item partial.

## Decisions

### Same template, FlexForm and partial as on `main`

`settings.groupByRole` is a `check` field with `checkboxToggle`, default `1`,
and `plugin.tx_academiccontacts4pages.settings.groupByRole = 1` is the
TypoScript default a stored FlexForm without the key falls back to. As on
`main`, an inverted flag was rejected: the element needs the shipped setup for
its partial paths anyway. The item
partial `Contacts/Item.html` receives `contact`, `role`, `profile`,
`contract`, `settings`, `data` and `grouped`, and renders `Profile/Item` as
`List.html` did, with the role name in a `p.academic-contacts4pages__role`
above it in the flat branch.

### Tests

The functional tests of `main` for the option and the item partial are ported
with their fixtures. Because the test class here lacks the heading level and
without-role tests of `main`, two of them are ported as well, to show the
default markup still reaches `Profile/Item` unchanged. A new test points the
partial root path constant at a directory without `Contacts/Item.html` and
asserts every card still renders: Extbase adds the extension's own partial
path as the lowest-priority path on TYPO3 v12 as well. The FlexForm
default is pinned by a unit test that reads the XML, since the
FormEngine-based trait of `main` does not exist here.

### Changelog placement

The `Feature-` entry is the file of `main`, in `Documentation/Changelog/2.4/`
on both branches.

## Risks / Trade-offs

- [A project's `List.html` override does not get the option] → The
  `Feature-` changelog entry says so and shows the item partial as the
  smaller override.
- [A project already ships a partial named `Contacts/Item.html` in a partial
  root path of the plugin] → It is now rendered for every contact. The
  changelog entry names the new partial so it can be checked.

## Open Questions

None.
