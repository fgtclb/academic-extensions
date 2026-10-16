## Context

See `proposal.md` for the motivation. On `main`:

- `Partials/Profile/Item/DetailLink.html` of `academic_persons` renders no
  address when `settings.detailLink` is `none`. Only that value switches the
  link off, so a caller whose settings do not carry the key links, by design
  of ACE-807.
- The contact list renders `Profile/Item` through `Contacts/Item.html` with
  its own plugin settings. `Configuration/TypoScript/List/setup.typoscript`
  maps the persons constants the partials read into those settings:
  `detailPid`, the list crop variant, the placeholders and the phone link
  prefix. `detailLink` is missing.
- The constants of `academic_persons` reach a site of the contact list through
  `Configuration/TypoScript/List/include_static_file.txt`, which names the
  persons TypoScript. The site set path reads that file as well
  (`SysTemplateTreeBuilder::handleSetInclude()`), so the static template of
  the contact list alone and the site set of the contact list both resolve the
  constant, which `SiteSetDeliveryTest` pins for `detailPid`.
- On the site set path, the site settings are added as constants after the
  constants of every set (`SysTemplateTreeBuilder::createSiteTemplateInclude()`,
  v13 and v14), so a site setting wins over the default the persons
  constants assign, also when the contact list set reads them a second time.
  The setting is declared by the aggregate set `fgtclb/academic-persons`.
- `ContactsProcessor` assigns the contacts of a page, their roles and the
  contacts without a role to a page template. It assigns no settings.

## Goals / Non-Goals

**Goals:**

- The contact list follows the setting on both delivery paths, with the
  default unchanged.

**Non-Goals:**

- See the proposal.

## Decisions

### Map the constant, next to the detail page

`detailLink = {$plugin.tx_academicpersons.detailLink}` in the settings of
`plugin.tx_academiccontacts4pages`, next to `detailPid`. It is the mechanism
the contact list already uses for every other persons setting the partials
read, and the partial needs no change.

Rejected: an own option in the FlexForm of the contact list, with
`detailLink` in its `ignoreFlexFormSettingsIfEmpty`, as the persons elements
have. Nobody asked for a contact list that differs from the persons elements
of its site, and it would add a field, labels and a third state to an element
that has two options today. It can be added later on top of this mapping,
which would stay its fallback. A site that needs the contact list to differ
sets `plugin.tx_academiccontacts4pages.settings.detailLink` in its TypoScript
setup, which the manual shows.

### No guard against an unresolved constant

A site that includes the setup of the contact list without the persons
TypoScript, the deprecated 2.3 path without its persons entry or an import
by file, gets the literal `{$plugin.tx_academicpersons.detailLink}`. That is
not `none`, so the names link as before. `detailPid` breaks the same way on
such a site already, and the manual names the constants the setup reads.

### The page contacts data processor stays as it is

It provides records, not rendering, and has no settings to carry the value.
A page template that renders the profile item passes settings of its own, and
the template documentation of `academic_persons` already describes how such a
caller controls the link. The manual of the contact list says that the partial
reads the settings the page template passes.

## Risks / Trade-offs

- [A site that switched the persons elements to "no link" sees the names of
  the contact list lose their link as well] → That is what the issue asks for.
  The setting is unreleased, so no released site has it set. The TypoScript
  override above brings the links back for the contact list alone.
- [The contacts list test of ACE-807 pinned the old behaviour] → It is flipped
  to the new one rather than deleted, so the plain TypoScript setup of that
  test class keeps covering the mapping.
