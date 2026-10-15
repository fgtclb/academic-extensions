## Context

The demand factory of each list (`Classes/Factory/DemandFactory.php` of
`academic_partners`, `academic_programs` and `academic_projects`) builds the
demand in two branches: from the settings for a request without a demand, and
from the submitted demand otherwise. The templates render a part of the form
only while its setting does not hide it (`settings.hideFilter`,
`settings.hideSorting`, `settings.hideActiveState`, the projects labels read
"Show ..." through `invertStateDisplay`, the stored value is the same). A
hidden part therefore submits nothing, and the second branch drops its preset.

`createDemandArguments()` turns the resulting demand into the arguments of the
`303` target, and the controllers build the pagination links from the same
arguments. The active filter partials render a category tag only while the
filter is shown, and the project state tag only while the state select is
shown.

The program finder posts `filterCollection[<type>]` for the types it offers,
and no sorting. Since 3.0 (ACE-91) a program demand without a sorting keeps the
sorting of the element.

## Goals / Non-Goals

**Goals:**

- One rule per form part in all three factories, read from the same settings
  the templates read, so a template override changes nothing.
- The redirect target, the pagination links and the tags follow without a
  change of their own.

**Non-Goals:**

- A change of `createDemandArguments()` or of its signature. The demand it
  receives is already right.
- The fallback of a partner or project demand that carries no sorting at all.
  With the sorting hidden it is covered by the new rule, with the sorting shown
  the form always submits one.
- A redirect per list element, see decision 5.

## Decisions

1. **The factory reads the hide settings.** For a hidden sorting or state the
   value comes from the settings whatever the request carries, through the
   same code the request without a demand runs: `applySortingFromSettings()`
   for the partner and program sorting, `ProjectDemand::setSorting()` and
   `ActiveState::tryFromDefault()` for projects. Rejected: hidden form fields,
   which a visitor can change, and which every override of the form partial
   would have to carry.
2. **A hidden category filter narrows, in all three extensions.** Every
   default category stays, and the categories a submission or a link carries
   are added. A record has to carry every category, so a request can narrow
   the list but never leave what the editor set. The program finder opens
   lists that may hide their filter, and the finder spec has such a list
   filtered by the finder's selection. A hidden filter without default
   categories is filtered by a link as before. Rejected: the settings alone,
   which would make a finder useless for such a list and drop the one use a
   link to such a list had, and replacing the defaults per type, which would
   let a request leave the selection the editor fixed.
3. **A filter keyed by category type keeps the defaults of the types it does
   not name, in all three extensions.** The list form and the program finder
   submit `filterCollection[<type>]`, the list form with an empty value for
   "All", so a named type replaces its defaults and a type the form does not
   offer, left out by the filter types of the site or the element or not
   offered by the finder, keeps them. A `filterCollection` with the key
   `categories`, the shape of the redirect target, the tags and the pagination
   links, and a demand without a `filterCollection` are a complete selection:
   the URL shape carries no type, and a URL without categories is what
   clearing every filter leads to. Rejected: keeping the defaults whenever a
   demand carries no `filterCollection`, which would bring a cleared preset
   back after the redirect.
4. **One rule in `category_types`.** `CategoryFilterNormalizer::keepsPresets()`
   decides whether the defaults take part in a demand, which spares the
   factories the query for the defaults where they cannot, and
   `toUidListKeepingPresets()` builds the uid list. Both read only the request
   value and the defaults by type, so they are unit tested without a
   database. The factories keep one shape: sorting, `resolveCategories()`,
   the settings that always apply. Rejected: three private copies of the rule.
5. **The redirect of two lists of one plugin stays.** The first list on the
   page answers a submission and builds the URL from its own settings, so its
   hidden presets reach the URL and override another list's visible part. A
   redirect per element would need the element in the URL. The manuals name
   the limitation.

## Risks / Trade-offs

- [A URL bookmarked while a list showed its filter keeps narrowing the list
  after the editor hid the filter] → The list is never wider than the editor
  set it.
- [A tag for a default category of a type the list's filter does not offer]
  → It was shown before for a request without a demand and is now shown after
  a submission as well. Its link carries the complete selection and removes
  the category. Restricting the tags to the offered types is left for a change
  of its own.
