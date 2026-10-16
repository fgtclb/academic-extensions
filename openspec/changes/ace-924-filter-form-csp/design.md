## Context

Ten selects submit the forms of the partner, project and program lists through
`additionalAttributes="{'onchange': 'this.form.submit()'}"`, in
`Partner/DemandSorting.html`, `Partner/DemandCategories.html`,
`Project/DemandSorting.html`, `Project/DemandCategories.html`,
`Project/DemandActiveState.html`, `Program/DemandSorting.html` and
`Program/DemandCategories.html`. The frontend policy of
`cms-frontend/Configuration/ContentSecurityPolicies.php` extends `script-src`
by the nonce proxy and has no `script-src-attr`, so an enforced policy blocks
every handler attribute. Verified on TYPO3 12.4.45 and 13.4.36: an extension's
`Configuration/ContentSecurityPolicies.php` is read for every active package,
its frontend scope applies to every site with `inheritDefault` (the default),
and `Map::fromEntries()`, `Scope::frontend()`, `MutationMode::Extend`,
`Directive::ScriptSrcAttr`, `SourceKeyword::unsafeHashes` and
`HashValue::hash()` exist on both. The `main` change of the same name is the
template. What differs here: TYPO3 v12 instead of v14, Bootstrap markup instead
of the `ace-*` classes in the partials, no redirect after a filter submission,
and `academic_programs` still has the handlers.

## Goals / Non-Goals

**Goals:** the selects work under the enforced default policy without a
change of a site, the form can be submitted without JavaScript, and a new
handler cannot reach a template without the hash that allows it.

**Non-Goals:** a script, the map tiles, policies TYPO3 does not send, and the
profile editor of `academic_persons_edit`.

## Decisions

- **Keep the handlers, allow them by hash, programs included.** The maintainer
  chose this on `main`. On this branch it is also the only way for
  `academic_programs`: its 3.0 module updates the list in place, which needs
  the redirect of a filter submission and `f:asset.module`, and v12 has no
  module ViewHelper.
- **`script-src-attr` with `MutationMode::Extend`.** The attribute directive
  leaves script elements to `script-src`, so the nonce and an `'unsafe-inline'`
  a site gave its scripts keep working. `Extend` copies the sources of the
  existing ancestor first, the core's way to add sources. On v12 the nonce of
  `script-src` is copied as well, which is harmless in an attribute directive.
  `Set` would drop a hash another package added before.
- **One handler text, one hash, one class.** All ten handlers use
  `this.form.submit()`. Each file returns
  `new InlineEventHandlerMutation('this.form.submit()')`, a final `Mutation`
  of `academic_base` that hashes the texts it is given. The class is what the
  listener recognises: by content alone, a site mutation with the same sources
  would count as ours. The three extensions depend on `academic_base` already.
  The class and the partners and projects files are the ones of `main`.
- **A listener for sites that allow inline scripts.**
  `DropInlineEventHandlerHashesWhereInlineIsAllowed` listens to
  `PolicyMutatedEvent`, which 12.4.45 ships byte for byte as 13.4.36, and
  removes `script-src-attr` from a frontend policy when a policy built from a
  new `Policy` and every other mutation has none, and the fallback,
  `script-src` or else `default-src`, contains `'unsafe-inline'` or does not
  exist. Where the site declares `script-src-attr` as well and it carries
  `'unsafe-inline'`, our hash instances are taken out with `reduce()`. It uses
  only `has()`, `containsDirective()`, `reduce()` and `remove()`:
  `Policy::get()` does not exist on 12.4, and `HashValue` has no equality on
  any version. It is registered with the `event.listener` tag in the
  `Services.yaml` of `academic_base`, because TYPO3's `#[AsEventListener]` does
  not exist on v12, and it is a `final class`, not a `final readonly class`,
  which PHP 8.1 does not know. The mutation keeps Symfony's `#[Exclude]`,
  which `academic_base` uses on this branch already. Rejected:
  `PolicyPreparedEvent`, which does not exist on 12.4, and `'unsafe-inline'` in
  `script-src-attr`, which would allow every handler of the site on pages whose
  nonce makes the browser ignore its `'unsafe-inline'`, on v12 every page that
  uses a nonce or has uncached content.
- **`<noscript>` instead of a button a script hides.** It sits in a Bootstrap
  row of its own after the row of the selects, `row mt-3` with a `col-12`
  and `btn btn-primary`, because a `<noscript>` inside the flex row would be a
  flex item without a column width.
- **The guard test is the one of `main`, with one exception.** It requires
  each `ContentSecurityPolicies.php` and counts the hashes of the
  `InlineEventHandlerMutation`s in it. The profile editor of
  `academic_persons_edit` has eight handlers here, six built from a variable,
  and loads CKEditor 4 from `cdn.ckeditor.com`, which the default policy
  blocks too. Allowing two of its handlers would make nothing work, so the
  package is listed in `NOT_ALLOWED` with the number of its handlers and its
  reason, and the entry fails once that number changes. Rejected: a per-file
  exclusion, which would leave the rest of the package unguarded in the same
  way, and dropping the test on this branch.
- **Functional tests read the header.** They enforce the policy through the
  feature flag, parse `Content-Security-Policy` into directives and check every
  `on…` attribute of the rendered page against `script-src-attr`. The
  submission test posts the filter as the form sends it and expects the
  filtered list, as this branch answers a submission with the list itself.
  The scenarios of a site policy write its `csp.yaml` and reset the scopes of
  the policy. The change event of a site configuration that `main` also
  dispatches does not exist on 12.4, and the scenarios need none.

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
- TYPO3 v12 never drops a nonce: a page that uses one, and every page with
  uncached content such as the plugins of these forms, sends it in
  `script-src`. On a site with `'unsafe-inline'` a browser blocks every inline
  handler there unless the site takes the nonce out, the handlers of the form
  as those of the site. On v13 that holds for a page that keeps its nonce, the
  partner map for example. The forms follow the site rather than work where
  its own handlers do not.
- `<noscript>` does not help a visitor whose scripts run while a stricter
  policy, from the web server for example, blocks the handler. The manuals
  name such a policy as not covered.
- A site package copy of a partial with another handler text keeps failing
  under the policy until the site adds its hash. The manuals show the
  `csp.yaml` for it.
- The profile editor of `academic_persons_edit` stays broken under an enforced
  policy on 2.x. The test documents why.
