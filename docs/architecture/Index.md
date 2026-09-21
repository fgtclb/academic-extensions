# Architecture

The rules the code follows, and the reasoning behind them. Where the code base
does not yet follow a rule consistently, the page says so rather than
describing an intention as if it were the state.

This branch supports **TYPO3 v12 and v13**.

## The short version

- A difference between core versions is a **switch** while it is one or two
  lines, and a **class split** only when a whole class has to differ. Both
  exist here: `academic-base` carries a real `Core12/`/`Core13/` split.
- Services are **stateless**. New services must be; existing ones must not gain
  state.
- Never hand a raw array to `in()` or `notIn()`, build a constraint on the
  query builder that executes it, and order every result a caller renders or
  limits. All three rules come from defects that reached a release, and the
  first one bites hardest on v12, which has no guard at all.
- One YAML in `academic_persons` drives field validation for **both** the backend
  FormEngine and the frontend edit form. It ships there, not in
  `academic_persons_edit`, because the TCA needs it.
- In the frontend edit forms, a `disabled` or `readOnly` property is **never**
  written, whatever the request carries. The shipped `profile` set locks the
  three name fields that way, which is intended and regularly misread.
- A content element template renders the frame, the spacing, the `c{uid}`
  anchor and the header **only** when it goes through the `Default` layout of
  whichever package provides `lib.contentElement`. A template without it gets
  none of them, however complete its Appearance tab looks in the backend.
- The category summary of the page module is **one implementation in
  `category_types`** and three four-line listeners on
  `ModifyPageLayoutContentEvent`, registered with the `event.listener` tag
  because `#[AsEventListener]` does not exist on v12. It shipped three times as
  a partial before, registered as an override of a core backend partial that no
  core template renders — only `academic_programs` ever showed it, and a rename
  in March 2023 ended that.
- TypoScript and page TSconfig exist **once** on disk and are delivered twice:
  a site set points at the very files the static template registration points
  at. Content elements are hidden globally and re-enabled per component. Site
  sets do nothing on v12, so the static half is the load-bearing one here.

## Pages

| Page                                                            | Contents                                                                                                                                                       |
|-----------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [Core version aware code](core-version-aware-code.md)           | The four mechanisms in use, the `Core12/`/`Core13/` split in `academic-base`, and how version specific tests are grouped.                                      |
| [Dependency injection](dependency-injection.md)                 | How services are configured across the extensions, why they must be stateless, and which Symfony and TYPO3 attributes exist on both v12 and v13.               |
| [Class design](class-design.md)                                 | `final`, `readonly`, constructor versus method injection, data objects, and the traps in Extbase models.                                                       |
| [Database queries](database-queries.md)                         | Quoting value lists, keeping a constraint on the builder that executes it, and ordering every result a caller renders or limits.                               |
| [Backend select items](backend-select-items.md)                 | What an `itemsProcFunc` handler is handed on both core versions, the page TSconfig path of a FlexForm field, and the narrowing that silently drops a relation. |
| [Frontend-user contact import](frontend-user-contact-import.md) | How telephone and fax data from `fe_users` is identified, typed and synchronized.                                                                              |
| [Validation settings](validation-settings.md)                   | The one YAML that drives both the backend FormEngine and the frontend edit form, its flags, and how an installation overrides it.                              |
| [Form data transformation](form-data-transformation.md)         | How a submitted value reaches the model, why `disabled` wins over everything, and the shipped defaults that surprise people.                                   |
| [Page module category summary](page-module-category-summary.md) | The listener and the shared renderer behind the category table of the page module, the override key, and why the labels come from the registry.                |
| [TypoScript and site sets](typoscript-and-site-sets.md)         | The layout that serves site sets and static templates from one physical copy, hide-by-default, and why v12 only ever sees the static half.                     |
| [Content element rendering](content-element-rendering.md)       | The two rendering shapes, what the `Default` layout renders, and why a content element template needs it.                                                      |

## See also

- [Dual core setup](../development/dual-core-setup.md) — running against both
  core versions.
- [Quality gates](../development/quality-gates.md) — PHPStan is configured per
  core version.
- [Testing](../testing/Index.md) — how core version differences are covered by
  tests.
