## Why

Three tables are inline children of more than one parent at once, and every
relation writes the child's one `sorting` column. On every save of a parent,
`RelationHandler::writeForeignField()` renumbers that parent's children 1..n
in the order of its form, into the relation's `foreign_sortby`, or into the
table's `ctrl.sortby` when the relation declares none:

| Child                                    | Owning relation                                                        | Second relation                                                                                                               | Column the second one writes     |
|------------------------------------------|------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------|----------------------------------|
| partnership                              | partner page `partnerships` (`foreign_sortby = sorting`)               | role `partnerships` (no `foreign_sortby`)                                                                                     | `sorting`, through `ctrl.sortby` |
| contract                                 | profile `contracts` (`foreign_sortby = sorting`)                       | organisational unit `contracts` (`foreign_sortby = sorting`)                                                                  | `sorting`                        |
| page contact (`academic_contacts4pages`) | page `tx_academiccontacts4pages_contacts` (`foreign_sortby = sorting`) | contract `tx_academiccontacts4pages_contacts` (`foreign_sortby = sorting`) and contacts role `contacts` (no `foreign_sortby`) | `sorting`                        |

Saving a partner role renumbers its partnerships across all partner pages,
saving an organisational unit renumbers its contracts across all profiles, and
saving a contract or a contacts role renumbers its contacts across all pages.
Each can silently rearrange the list an editor arranged on the partner page,
the profile or the page. Partnership lists and teasers render in `sorting`
order since ACE-491, and a profile's contracts and a page's contacts have
always rendered in it, so the rearrangement reaches the frontend.

## What Changes

- `academic_partners` (`packages/fgtclb/academic-partners`): partnerships get
  a column `role_sorting`, and the role's `partnerships` relation declares it
  as its `foreign_sortby`.
- `academic_persons` (`packages/fgtclb/academic-persons`): contracts get a
  column `organisational_unit_sorting`, and the organisational unit's
  `contracts` relation declares it as its `foreign_sortby`.
- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`): page
  contacts get `contract_sorting` and `role_sorting`, declared by the contract
  and the contacts role relation.
- One DataHandler hook per extension appends a child to the list of a secondary
  parent as it joins one, because `writeForeignField()` fills the column only
  when that parent is saved - and the usual editing path assigns the parent from
  the child's own form instead.
- `academic_persons` additionally listens to the Extbase persistence events for
  the same rule: the profile editing frontend writes contracts through the
  repository, which no DataHandler hook sees.
- One upgrade wizard per extension seeds each new column from the current
  order within its parent (`sorting`, then `uid`), so the role and unit forms
  keep showing what they show today.
- A changelog entry per extension.
- TYPO3 v13 and v14 alike; the relation handling does not differ between them.

## Capabilities

### New Capabilities

- `academic-partners/partnership-order`: the order of a partner page's
  partnerships is the one arranged on that page, whatever else is saved.
- `academic-persons/contract-order`: the order of a profile's contracts is the
  one arranged on that profile, whatever else is saved.
- `academic-contact4pages/page-contact-order`: the order of a page's contacts
  is the one arranged on that page, whatever else is saved.

### Modified Capabilities

None.

## Impact

Four TCA relations, four new integer columns, three DataHandler hooks, one
Extbase persistence listener, three upgrade wizards, their tests and changelog
entries. Nothing renders a role's partnerships, a unit's
contracts or a contract's or contacts role's contacts in the frontend today,
so the new columns only order those backend forms.

## Non-goals

- Changing which relation owns `sorting`: the partner page, the profile and the
  page keep it, because they are what the frontend renders.
- Removing either second relation from its form, or making it read-only.
- Any other table with a second inline parent. A sweep of every inline
  relation in the extensions' TCA on 2026-09-19 found exactly these three; the
  implementation re-checks.

## Source

Found while correcting the ACE-491 changelogs on both branches (pull requests
#671 and #672, 2026-09-19): the partners entry had to say that saving a role
can rearrange a page's partnerships. Not from the project differences analysis;
adopted into the same pipeline. Tracked as **ACE-699**, filed once the
implementation was green, and archived as the last commit of the pull request
that carries it. Relates to ACE-491 and ACE-431.
