# Architecture

The rules the code follows, and the reasoning behind them. Where the code base
does not yet follow a rule consistently, the page says so rather than
describing an intention as if it were the state.

## The short version

- A difference between core versions is a **switch** while it is one or two
  lines, and a **class split** only when a whole class has to differ.
- Services are **stateless**. New services must be; existing ones must not gain
  state.
- Never hand a raw array to `in()` or `notIn()`, and build a constraint on the
  query builder that executes it. Both rules come from defects that reached a
  release.
- One YAML in `academic_persons` drives field validation for **both** the backend
  FormEngine and the frontend edit form. It ships there, not in
  `academic_persons_edit`, because the TCA needs it.
- In the frontend edit forms, a `disabled` or `readOnly` property is **never**
  written, whatever the request carries. The shipped `profile` set locks the
  three name fields that way, which is intended and regularly misread.
- Translation synchronisation writes go **through the DataHandler**, never
  through raw queries: nothing on the frontend read path repairs a stale
  `l10n_mode=exclude` value, so the translation rows themselves must be correct.
- TypoScript and page TSconfig exist **once** on disk and are delivered twice:
  a site set points at the very files the static template registration points
  at. Content elements are hidden globally and re-enabled per component.

## Pages

| Page                                                            | Contents                                                                                                                                              |
|-----------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------|
| [Core version aware code](core-version-aware-code.md)           | The version switches that exist today, what a `Core13/`/`Core14/` split would look like, and the APIs that cannot be migrated while v13 is supported. |
| [Dependency injection](dependency-injection.md)                 | How services are configured across the extensions, why they must be stateless, and which TYPO3 attributes are safe on both core versions.             |
| [Class design](class-design.md)                                 | `final`, `readonly`, constructor versus method injection, data objects, and the traps in Extbase models.                                              |
| [Database queries](database-queries.md)                         | Quoting value lists, and keeping a constraint on the builder that executes it.                                                                        |
| [Frontend-user contact import](frontend-user-contact-import.md) | How telephone and fax data from `fe_users` is identified, typed, synchronized and migrated.                                                           |
| [Validation settings](validation-settings.md)                   | The one YAML that drives both the backend FormEngine and the frontend edit form, its flags, and how an installation overrides it.                     |
| [Form data transformation](form-data-transformation.md)         | How a submitted value reaches the model, why `disabled` wins over everything, and the shipped defaults that surprise people.                          |
| [TypoScript and site sets](typoscript-and-site-sets.md)         | The layout that serves site sets and static templates from one physical copy, hide-by-default, and the `clear = 3` trap.                              |
| [Translation synchronization](translation-synchronization.md)   | Why profile translations are written through the DataHandler, the event chain that triggers it, and the contact4pages policy on top of it.            |

## See also

- [Dual core setup](../development/dual-core-setup.md) — running against both
  core versions.
- [Quality gates](../development/quality-gates.md) — PHPStan is configured per
  core version.
- [Testing](../testing/Index.md) — how core version differences are covered by
  tests.
