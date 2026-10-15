## Purpose

Defines how the partnership list and the partner logo teaser arrange the
partnerships of a page by their role, and where the partnerships without a
role, or with a role that is hidden or deleted, appear.

## ADDED Requirements

### Requirement: Partnerships with a role render in one group per role

While at least one partnership of a page has a role, the partnership list and
teaser SHALL render one group per role, in the order in which the roles first
occur among the partnerships of the page. Each group SHALL open with the name
of its role as heading, followed by the partners of that role in the order
arranged on the page. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Two roles on one page

- **WHEN** a visitor views a page whose partnerships carry two different roles
- **THEN** the element renders two groups, headed by the role names in the
  order the roles first occur on the page, each listing the partners of its
  role

#### Scenario: Every partnership has a role

- **WHEN** every partnership of the page has a role
- **THEN** the element renders the role groups only, and no list without a
  heading after them

### Requirement: Partnerships without a role render after the role groups

A partnership without a role SHALL render on every page that also holds
partnerships with a role. The partnerships without a role SHALL follow the
role groups in one list without a heading, in the order arranged on the page.
A partnership whose role is hidden or deleted SHALL render as a partnership
without a role. A page template that receives the partnerships of the page
SHALL receive the partnerships without a role as a list of their own as well.
This applies to TYPO3 v13 and v14 alike.

#### Scenario: A page mixes partnerships with and without a role

- **WHEN** a visitor views a page with partnerships that have a role and
  partnerships that have none
- **THEN** the element renders the role groups first, and after them the
  partners without a role in one list without a heading, in the order arranged
  on the page

#### Scenario: A partnership without a role in a translated language

- **WHEN** a visitor views such a page in a translated language
- **THEN** each partnership without a role renders once after the role groups,
  as its translation where it has one and as its default language record where
  it has none, and the role groups keep their heading in the visitor's
  language

#### Scenario: An editor removes the role of a partnership

- **WHEN** an editor removes the role of one partnership of a page whose other
  partnerships keep their role
- **THEN** the partner of that partnership still renders, after the role
  groups

#### Scenario: The role of a partnership is hidden or deleted

- **WHEN** a visitor views a page whose partnerships have a visible role, and
  one partnership has a role that is hidden or deleted
- **THEN** that partnership renders like a partnership without a role, after
  the role groups, and no group is headed by the hidden or deleted role

#### Scenario: A hidden partnership

- **WHEN** a partnership of such a page is hidden, whatever its role
- **THEN** it renders neither in a role group nor after them

### Requirement: Partnerships without any role render in one list

While no partnership of a page has a role, the partnership list and teaser
SHALL render all partners of the page in one list without a heading, in the
order arranged on the page.

#### Scenario: No partnership has a role

- **WHEN** a visitor views a page whose partnerships have no role
- **THEN** the element renders one list of all partners without a heading
