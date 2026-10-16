## Context

`PartnerController::partnershipsListAction()` and `partnershipsTeaserAction()`
read the partnerships of the page through `PartnershipRepository::findByPid()`,
ordered as arranged on the page, and assign them with the roles
`PartnershipRoles::collect()` returns: keyed by uid, the role in a language
other than the default one winning (ACE-430). `PartnershipProcessor` assigns
the same two variables to the page template. `collect()` skips a partnership
without a role, and the templates `Partner/PartnershipsList.html` and
`Partner/PartnershipsTeaser.html` render, while roles exist, only the
partnerships whose `role.uid` matches a role. A partnership without a role
matches none.

The backport analysis against `main` (`9c5843056`, before the change):

- `PartnershipProcessor.php` and the test TypoScript of the processor are
  identical on both branches. The `main` hunks apply as they are.
- `PartnershipRoles.php` differs in one line: it is `final class` here and
  `final readonly class` on `main`, since this branch compiles against PHP
  8.1. The new method is the same.
- `PartnerController.php` differs widely. The two partnership actions here
  assign no `record` and dispatch no plugin view event, and the class is not
  final. Only the assignment of the new variable is the same.
- The templates differ in their markup. Here each role group is a bare
  `<div class="row">` that opens with the role heading of the `Partner/Header`
  partial, the flat list is a bare row as well, and there is neither an
  `ace-itemlist` wrapper nor a content element header partial.
- The functional test harness is at parity for this test: the plugin
  rendering trait and the site based test trait are present, and
  `PartnershipsPerLanguageTest` reads the same plugins with the markup of this
  branch.

## Goals / Non-Goals

**Goals:**

- The same behaviour, the same variable name and the same service method as on
  `main`, so a template override written against one branch reads the variable
  the same way on the other.
- The ACE-430 handling of the roles is not touched.

**Non-Goals:**

- Aligning the markup of the templates with `main`. The new block follows the
  markup of this branch.

## Decisions

1. **A second method on `PartnershipRoles`**, `withoutRole()`, returning the
   partnerships without a role as a list in the order of the partnerships it
   is given, as on `main`. Rejected for the same reasons as there: filtering
   in the template, which leaves the processor without the list, and widening
   the return type of `collect()`.
2. **The variable is `partnershipsWithoutRole`**, in both actions and in the
   processor, as on `main`.
3. **The block is a bare `<div class="row">`** with the columns and item
   partials of the flat list, after the role groups inside `f:then`, without a
   heading and without `grouped`. That is the markup every other item list of
   these templates has here. The `ace-itemlist` wrapper of `main` was rejected,
   because it would be the only one on this branch and a stylesheet written
   for 2.x knows no such class.
4. **A hidden or deleted role counts as no role.** Extbase resolves such a
   role to `null` in the frontend on TYPO3 v12 and v13 alike, which the
   functional test pins on both, so `withoutRole()` lists the partnership
   without a check of its own. The maintainer decided on `main` to show these
   partnerships rather than to hide them with their role: hiding a role was
   never a documented way to hide its partnerships, and the partnership has
   its own hidden flag. Rejected: reading the raw role uid to keep them out,
   which would need a query of its own and would make a partnership vanish
   for a reason the editor cannot see in the page form.
5. **The flat list branch stays as it is.** While no partnership has a role,
   `partnershipRoles` is empty and the templates take the existing `f:else`,
   so `partnershipsWithoutRole`, which then holds every partnership, is not
   rendered twice.
6. **A new capability `academic-partners/partnership-role-groups`** rather than
   an ADDED requirement in `academic-partners/partnership-languages` or
   `academic-partners/partnership-order`. The language spec states which
   records render in which language, and the order spec that the page order
   survives saving another record. Where a partnership without a role renders
   holds in the default language alone and has nothing to do with either, so
   the requirement would bend the purpose of the spec it joins. The same
   capability path as on `main` also keeps the specs of both branches
   comparable, which is the only thing that is shared about them.

## Risks / Trade-offs

- [A site hid a role to hide its partnerships] → They show again after the
  role groups. The `Important` entry says so and points to hiding the
  partnerships themselves.
- [A site package overrides one of the templates] → It keeps leaving the
  partnerships without a role out. The `Important` entry in `Changelog/2.4`
  names the variable with a snippet, and the upgrade guide from 2.3 lists it
  for both templates.
- [`partnershipsWithoutRole` is a plain array while `partnerships` is a query
  result] → Templates only iterate and test it, which both support.
