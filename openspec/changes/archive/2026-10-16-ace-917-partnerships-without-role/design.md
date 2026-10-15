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

`academic_contacts4pages` had the same shape and assigns `contactsWithoutRole`
since ACE-322, rendered after the role groups without a heading and without
`grouped`, so the partner names keep the heading level of the flat list.

## Goals / Non-Goals

**Goals:**

- The list of partnerships without a role is computed once, by the service
  that collects the roles, for both actions and the processor.
- The ACE-430 handling of the roles is not touched.

**Non-Goals:**

- A value object holding partnerships, roles and the rest, as
  `academic_contacts4pages` introduced with its provider (ACE-783). Two
  arrays from one stateless service are enough for three callers.

## Decisions

1. **A second method on `PartnershipRoles`.** `withoutRole()` returns the
   partnerships without a role as a list, in the order of the partnerships it
   is given, which is the page order of `findByPid()`. Rejected: filtering in
   the template with `<f:if condition="!{partnership.role}">`. It would work
   without a new variable, but an override could not ask whether the block is
   needed at all without looping, and the processor would leave a page
   template to repeat the condition. Rejected as well: returning both lists
   from `collect()`, which would change the return type of a method the
   processor and both actions already rely on.
2. **The variable is `partnershipsWithoutRole`**, after
   `contactsWithoutRole`, in both actions and in the processor.
3. **No heading and no `grouped` for that list.** The partner names render at
   the level of the flat list, so they do not read as part of the last role
   group in the document outline. This matches ACE-322. A configurable heading
   was rejected by the maintainer.
4. **The processor gets the variable too.** It is the third caller of
   `collect()`, and a page template that groups by role would lose the same
   partnerships. `academic_contacts4pages` gave its processor the list only
   later (ACE-783), after the two outputs had drifted apart.
5. **The flat list branch stays as it is.** While no partnership has a role,
   `partnershipRoles` is empty and the templates take the existing `f:else`,
   so `partnershipsWithoutRole`, which then holds every partnership, is not
   rendered twice.

6. **A hidden or deleted role counts as no role.** Extbase resolves such a
   role to `null` in the frontend, so `withoutRole()` lists the partnership
   without a check of its own. The maintainer decided to show these
   partnerships rather than to hide them with their role: hiding a role was
   never a documented way to hide its partnerships, and the partnership has
   its own hidden flag. Rejected: reading the raw role uid to keep them out,
   which would need a query of its own and would make a partnership vanish
   for a reason the editor cannot see in the page form.

## Risks / Trade-offs

- [A site hid a role to hide its partnerships] → They show again after the
  role groups. The `Important` entry says so and points to hiding the
  partnerships themselves.

- [A site package overrides one of the templates] → It keeps leaving the
  partnerships without a role out. The `Important` entry in `Changelog/2.4`
  names the variable with a snippet, and the upgrade guides from 2.3 list it
  for both templates.
- [`partnershipsWithoutRole` is a plain array while `partnerships` is a query
  result] → Templates only iterate and test it, which both support.
