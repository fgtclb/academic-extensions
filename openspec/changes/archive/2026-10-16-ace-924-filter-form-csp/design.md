## Context

Seven selects submit the forms of the partner and project lists through
`additionalAttributes="{'onchange': 'this.form.submit()'}"`, in
`Partner/DemandSorting.html`, `Partner/DemandCategories.html`,
`Project/DemandSorting.html`, `Project/DemandCategories.html` and
`Project/DemandActiveState.html`. The frontend policy of
`cms-frontend/Configuration/ContentSecurityPolicies.php` extends `script-src`
by the nonce proxy and has no `script-src-attr`, so an enforced policy blocks
every handler attribute. Verified on TYPO3 12.4, 13.4.35 and 14.3.7: an
extension's `Configuration/ContentSecurityPolicies.php` is read by the package
service provider of every active package, its frontend scope applies to every
site with `inheritDefault` (the default), site sets cannot carry mutations,
and `SourceKeyword::unsafeHashes`, `Directive::ScriptSrcAttr`,
`HashValue::hash()` and `PolicyMutatedEvent` with the same API exist on all
three. `PolicyProvider::provideFor()` hands the mutation collections to the
event without their origin, `Policy::get()` does not exist on 12.4, a
`HashValue` implements no equality, and the clones of a policy share the map
of their directives, so the `defaultPolicy` of the event carries every
mutation once they are applied.

## Goals / Non-Goals

**Goals:** the selects work under the enforced default policy without a
change of a site, the form can be submitted without JavaScript, and a new
handler cannot reach a template without the hash that allows it.

**Non-Goals:** a JavaScript module, the map tiles, policies TYPO3 does not
send, `academic_programs` and `academic_persons_edit` on branch `2`.

## Decisions

- **Keep the handlers, allow them by hash.** The maintainer chose this over
  the module `academic_programs` received in 3.0. The module would need a
  script, a switch, JavaScript tests and documentation per extension for a form
  that only submits, and a project copy of a partial that keeps the handler
  would still need the hash.
- **`script-src-attr` with `MutationMode::Extend`.** The attribute directive
  leaves script elements to `script-src`, so the nonce and an `'unsafe-inline'`
  a site gave its scripts keep working. `Extend` copies the sources of the
  existing ancestor first, the core's way to add sources. `Set` would drop a
  hash another package added before.
- **One handler text, one hash, one class.** All seven handlers already use
  `this.form.submit()`. Each file returns
  `new InlineEventHandlerMutation('this.form.submit()')`, a final `Mutation`
  of `academic_base` that hashes the texts it is given, readable in the source
  instead of a base64 literal. The class is what the listener recognises: by
  content alone, a site mutation with the same sources would count as ours.
  Partners and projects both depend on `academic_base`, so the shared place
  keeps them independent of each other.
- **A listener for sites that allow inline scripts.**
  `DropInlineEventHandlerHashesWhereInlineIsAllowed` listens to
  `PolicyMutatedEvent` and removes `script-src-attr` from a frontend policy
  when a policy built from a new `Policy` and every other mutation has no
  `script-src-attr`, and the fallback, `script-src` or else `default-src`,
  contains `'unsafe-inline'` or does not exist. Where the site or another
  package declares `script-src-attr` as well and it carries `'unsafe-inline'`,
  the listener takes our hash instances out of it with `reduce()` and keeps
  `'unsafe-hashes'`. It uses `has()`, `containsDirective()`, `reduce()` and
  `remove()`, which 12.4 has, and compares hashes by instance only. It is
  registered with
  TYPO3's `#[AsEventListener]` here and by a `Services.yaml` tag on branch `2`.
  Rejected: replacing our sources by `'unsafe-inline'` in `script-src-attr`,
  which would allow every handler of the site on pages whose nonce makes the
  browser ignore its `'unsafe-inline'`, and `PolicyPreparedEvent`, which knows
  whether the nonce stays but does not exist on 12.4.
- **`<noscript>` instead of a button a script hides.** Visitors with JavaScript
  see no change, and nothing has to be shipped to hide it. The button sends no
  field of its own, so the request equals the one of the handler and the
  redirect of `list-filter-urls` applies as it is. It sits after the row of
  the selects, inside `.ace-filters`, so the grid of the selects is unchanged.
- **The guard test loads the policy file.** `InlineEventHandlerTest` requires
  each `ContentSecurityPolicies.php` and reads the `Map` it returns, rather
  than parsing PHP text. It counts hashes of `Extend` and `Append` mutations of
  `script-src-attr` in the generic frontend scope that also carry
  `'unsafe-hashes'`. The templates are scanned with two patterns, an `on…`
  attribute after whitespace and an `on…` key of a Fluid array after `{` or
  `,`, and a snippet test proves both. A second test reports hashes no handler
  needs, so the policy does not grow stale.
- **Functional tests read the header.** They enforce the policy through the
  feature flag, parse `Content-Security-Policy` into directives and check every
  `on…` attribute of the rendered page against `script-src-attr`, so a handler
  added in a way the unit test misses is still caught there.

## Risks / Trade-offs

- Once `script-src-attr` exists, a browser no longer falls back to
  `script-src` for attributes. A site that allowed its own handlers in
  `script-src` by hash has to add them there. One that allowed them with
  `'unsafe-inline'` keeps them through the listener. The 2.4 entries say so.
- A `script-src-attr` that another `PolicyMutatedEvent` listener adds outside
  the mutation collections is invisible to the rebuilt policy, so the listener
  takes the directive for ours and removes it where the fallback allows inline
  scripts.
- Recognition by class relies on TYPO3 handing over the mutation objects
  unchanged. A future policy cache in `PolicyProvider` that rebuilt them from
  their values would make the listener a no-op.
- A package loaded before the academic extensions that adds `'unsafe-inline'`
  to `script-src`, together with a hash of its own in `script-src-attr`,
  leaves the hash of the form out while the other hash still disables
  `'unsafe-inline'` in the browser. The form handlers are blocked there. A
  portable check for a remaining hash needs `Policy::get()`, which TYPO3 12
  does not have.
- On a site with `'unsafe-inline'`, a page that keeps a nonce in `script-src`,
  the partner map with its script for example, blocks the handlers of the
  form as it blocks those of the site. The forms follow the site there rather
  than work where its own handlers do not.
- `<noscript>` does not help a visitor whose scripts run while a stricter
  policy, from the web server for example, blocks the handler. The manuals
  name such a policy as not covered.
- A site package copy of a partial with another handler text keeps failing
  under the policy until the site adds its hash. The manuals show the
  `csp.yaml` for it.
