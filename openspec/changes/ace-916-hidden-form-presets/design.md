## Context

The demand factory of each list (`Classes/Factory/DemandFactory.php` of
`academic_partners`, `academic_programs` and `academic_projects`) builds the
demand in two branches: from the settings for a request without a demand, and
from the submitted demand otherwise. The templates render a part of the form
only while its setting does not hide it (`settings.hideFilter`,
`settings.hideSorting`, `settings.hideActiveState`, the projects labels read
"Show ..." through `invertStateDisplay`, the stored value is the same). A
hidden part therefore submits nothing, and the second branch drops its preset.

On this branch the form posts to the page and the list renders in the answer
to the post. There is no redirect, no pagination of these lists and no active
filter tags, so the demand the factory builds is all a visitor sees. The
partner and program factories already split the "Default sorting" setting
with `applySortingFromSettings()` (ACE-914). The program factory has no
fallback for a demand without a sorting (ACE-91 is not here), so a hidden
sorting is lost on every submission of its filter.

## Goals / Non-Goals

**Goals:**

- One rule per form part in the three factories, read from the same settings
  the templates read, so a template override changes nothing.
- The same shape of the factory as on `main`, so a later change applies to
  both branches the same way.

**Non-Goals:**

- A fallback of a partner, program or project demand that carries no sorting
  at all. With the sorting hidden it is covered by the new rule, with the
  sorting shown the form always submits one.
- The rule of 3.0 for a visible filter, where a submission keyed by
  category type replaces only the default categories of the types it names.
  On this branch a submission of the visible filter replaces all default
  categories, as before.

## Decisions

1. **The factory reads the hide settings.** For a hidden part the value comes
   from the settings whatever the request carries, through the same code the
   request without a demand runs: `applySortingFromSettings()` for the partner
   and program sorting, `ProjectDemand::setSorting()` and
   `ActiveState::tryFromDefault()` for projects. Rejected: hidden form
   fields, which a visitor can change, and which every override of the form
   partial would have to carry.
2. **A hidden category filter narrows, in all three extensions.** The default
   categories always apply, and categories a request carries are added to
   them. The repositories require every category of the filter, so the list
   can only get narrower than the editor set it. Rejected: ignoring the
   requested categories, which makes a link that narrows such a list useless,
   and replacing the default categories, which lets a request leave the
   selection the editor fixed. The same rule as on `main`.
3. **One private resolver per factory.** Each factory decides its categories
   in `resolveCategories()`, the same method in all three apart from the
   category group. Rejected: a shared service in `typo3-category-types`,
   which would add a dependency and a public API for twenty lines.
4. **No core version switch.** Nothing the change uses differs between TYPO3
   v12 and v13.

## Risks / Trade-offs

- [A URL bookmarked while a list showed its filter keeps narrowing the list
  after the editor hid the filter] → The list is never wider than the editor
  set it, and a URL without categories shows the default categories.
- [A project site that submitted categories through a form override to
  replace the default categories of an element with a hidden filter] → The
  categories now narrow instead. The content element has to show the filter
  for a visitor to replace them. The changelog entry names the rule.
