# Content Security Policy

TYPO3 sends a Content Security Policy for the frontend once a site enforces it,
with the feature `security.frontend.enforceContentSecurityPolicy` or the
disposition of its `csp.yaml`. Its `script-src` allows `'self'` and a nonce, so
a script element of the extensions passes as long as it is a file of the site
or is registered through the asset collector, which adds the nonce. An inline
event handler attribute does not pass: a nonce does not cover it, and the
default policy has no `'unsafe-inline'` for scripts.

## The handlers that exist

The sorting and filter forms of the partner list and map and of both project
lists submit the form from an inline handler on each select,
`additionalAttributes="{'onchange': 'this.form.submit()'}"`, seven of them in
`Partner/DemandSorting.html`, `Partner/DemandCategories.html`,
`Project/DemandSorting.html`, `Project/DemandCategories.html` and
`Project/DemandActiveState.html`. Under an enforced policy changing a select
did nothing, and the forms had no button to fall back on (ACE-924). The program
list replaced the same handlers by a module in 3.0, see
[Frontend JavaScript loading](frontend-javascript-loading.md), and the visitor
filter of the persons list has a submit button and no script. On branch `2` the
program list still has them and is covered the same way.

## Allowed by hash, in `script-src-attr`

`academic_partners` and `academic_projects` each ship a
`Configuration/ContentSecurityPolicies.php`. TYPO3 reads that file of every
active package on its own, when it builds the container, and applies its
mutations of the generic frontend scope to every site that inherits the
default policy, which is every site whose `csp.yaml` does not set
`inheritDefault: false`. Both files hold one mutation of `academic_base`:

```php
new InlineEventHandlerMutation('this.form.submit()'),
```

`FGTCLB\AcademicBase\Security\InlineEventHandlerMutation` is a
`Mutation` that extends `script-src-attr` by `'unsafe-hashes'` and the sha256
hash of every text it is given. It is internal to the academic extensions. The
header TYPO3 13.4.35 then sends for the partner list, without a nonced asset on
the page, starts like this:

```text
default-src 'self'; script-src 'self' 'report-sample'; …;
script-src-attr 'self' 'unsafe-hashes' 'sha256-osjxnKEPL/pQJbFk1dKsF7PYFmTyMWGmVSiL9inhxJY=' 'report-sample'; …
```

- **`script-src-attr`, not `script-src`.** The browser reads `script-src-attr`
  for event handler attributes only, and `script-src` keeps deciding about
  script elements as before. A hash in `script-src` would also make the browser
  ignore an `'unsafe-inline'` a site gave its script elements.
- **`Extend`, not `Set` or `Append`.** `Extend` first copies the sources of the
  nearest ancestor that exists, `script-src` or `default-src`, when the
  directive does not exist yet, and then appends. That is why `'self'` appears,
  and a nonce does on a page that consumes one, which is harmless in an
  attribute directive. `Set` would replace a hash another package added
  before. `Append` would leave out the sources of the ancestor, which makes no
  difference for an attribute, and `Extend` is the mode the core uses to add
  sources.
- **`'unsafe-hashes'` is required.** Without it a browser applies a hash to
  script elements only, never to an attribute.
- **The hash is of the exact text.** The browser hashes the attribute value as
  it reads it, after decoding character references. All seven handlers use
  the one text `this.form.submit()`, so one hash covers them. A handler with
  another text, even one that differs only in whitespace, needs a hash of its
  own.
- **A class of its own.** The mutation is recognised by its class in the
  listener below. TYPO3 hands the mutations to that listener without their
  origin, and a site may declare a mutation with exactly the same sources in
  its `csp.yaml`, which must count as the site's.

Site sets cannot carry Content Security Policy mutations, and a `csp.yaml`
belongs to a site, so the extension file is the one place an extension can
make its handlers work on every site at once. Replacing the handlers by a
module registered with `f:asset.module`, as the program list did, was the
alternative. It was not taken: it needs a script, a switch, JavaScript tests
and a changelog of its own per extension, for a form that only has to submit,
and an override of a partial that keeps the handler would still need the hash.

## A policy that allows inline scripts already

Once a policy has `script-src-attr`, the browser no longer falls back to
`script-src` for attributes, and next to a hash it ignores `'unsafe-inline'`.
A site that allows inline handlers of its own with `'unsafe-inline'` in
`script-src`, which TYPO3 13.4 rewards with fully cached pages because it then
drops the nonce, would lose them on every page through the mutation of an
extension. Extending the new directive by `'unsafe-inline'` in its `csp.yaml`
would not help either, for the same reason.

`DropInlineEventHandlerHashesWhereInlineIsAllowed` of `academic_base` listens
to `PolicyMutatedEvent` and removes `script-src-attr` from a frontend policy
when both hold:

- The directive exists only because of an `InlineEventHandlerMutation`. The
  listener builds the policy again from every other mutation of the event, to
  see whether the site or another package declares one as well.
- The directive the browser falls back to, `script-src` or else `default-src`,
  carries `'unsafe-inline'`, or neither exists.

A `script-src-attr` that the site or another package declares as well keeps
its sources. Where it carries `'unsafe-inline'` itself, the listener takes the
hashes of the `InlineEventHandlerMutation`s out of it with `reduce()`, by the
instances the mutations hold, which the policy keeps, and leaves
`'unsafe-hashes'`, which alone does not disable `'unsafe-inline'`.

The handlers of the forms then follow the configuration of the site, as the
site's own handlers do. Whether a nonce stays in `script-src` is decided after
the event: on a fully cacheable page TYPO3 13.4 and later drop it when it is
unused or `'unsafe-inline'` makes it unnecessary, and `'unsafe-inline'` then
applies. On a page that keeps the nonce, the
partner map with its script for example, a browser ignores `'unsafe-inline'`
and blocks every inline handler, those of the site and those of the form
alike. Keeping the hash there instead would have made the forms work where the
site's own handlers do not, and a `script-src-attr 'unsafe-inline'` would have
allowed every handler on pages where the site's policy blocks them.

The listener builds the other policy from a new `Policy`, not from the
`defaultPolicy` of the event. The clones of a policy share the map of their
directives, so after `PolicyProvider` has applied the mutations, the default
policy carries them as well. It uses only methods TYPO3 12.4 has too:
`Policy::get()` does not exist there, so it asks `has()` and
`containsDirective()`, and a `HashValue` implements no equality, so it compares
hashes by instance only.

Recognising the mutations by their class relies on TYPO3 handing over the
objects the `ContentSecurityPolicies.php` files return. `PolicyProvider` caches
no policy yet, its `@todo add policy cache per scope` says so. A cache that
rebuilt the mutations from their values would lose the class, and the
listener would then leave every policy as it is.

## Where the policy does not reach

- A site whose `csp.yaml` sets `inheritDefault: false` gets none of the
  mutations of the extension, and neither does a site that leaves the package
  out with `packages`, which TYPO3 reads inside an `enforce:` or `report:`
  section of the `csp.yaml` only. Such a site adds the two sources
  to its own `csp.yaml`, the manuals of both extensions show how.
- A copy of a partial that changes the handler text needs the hash of its own
  text in the `csp.yaml` of the site.
- A policy that the web server or a proxy sends is enforced next to the one of
  TYPO3, or in its place. TYPO3 does not know it, so it needs the same sources
  itself.
- The tiles of the partner map come from a tile server, OpenStreetMap by
  default, which `img-src` of the default policy does not allow. The site adds
  the host of its tile server to `img-src`. The extension does not, because the
  tile URL is a setting.

## The button without JavaScript

`Partner/SortingAndFilters.html` and `Project/SortingAndFilters.html` render a
submit button inside `<noscript>`, after the row of the selects, labelled
`filter.submit` of the extension. A visitor with JavaScript sees the form as
before. A button that a module hides was the alternative, and it needs the
module the change avoids. The button submits what the handler submits, the
form without a field of its own, so the redirect of a filter submission
applies unchanged, see [List filter URLs](list-filter-urls.md).

The button does not help where scripting is enabled and a policy blocks the
handler anyway, a policy of the web server for example. `<noscript>` renders
only where the browser runs no script at all.

## Tests

- `InlineEventHandlerTest` of `packages-dev/monorepo-shared` fails for every
  inline event handler of a template whose text no `InlineEventHandlerMutation`
  in the `ContentSecurityPolicies.php` of the same extension allows, and for
  every allowed hash no handler needs, see
  [Unit tests](../testing/unit-tests.md#inline-event-handlers).
- `DropInlineEventHandlerHashesWhereInlineIsAllowedTest` of `academic_base`
  runs the listener over policies built the way `PolicyProvider` builds them:
  the default frontend policy, `'unsafe-inline'` in `script-src` before and
  after the extension, in `default-src` with and without a `script-src`, no
  ancestor at all, a `script-src-attr` of the site, also one with the same
  hash, and the backend scope.
- `AcademicPartnersContentSecurityPolicyTest` and
  `AcademicProjectsContentSecurityPolicyTest` enforce the frontend policy and
  assert, for both plugins of each extension, that every handler of the page
  is allowed by `script-src-attr` with `'unsafe-hashes'`, that `script-src`
  carries neither the hash nor `'unsafe-hashes'` nor `'unsafe-inline'`, that a
  site with `'unsafe-inline'` in `script-src` gets no `script-src-attr`, that a
  `script-src-attr` of the site keeps its own hash and the one of the
  extension, that the form renders the one button inside `<noscript>`, and
  that a submission of the form redirects to the filtered list, on v13 and
  v14. They write the `csp.yaml` of the site and reset the scopes of the
  policy, which are singletons holding the site object of an earlier request
  of the process.

## See also

- [Frontend JavaScript loading](frontend-javascript-loading.md): how the
  extensions load their scripts, through the asset collector and its nonce.
- [List filter URLs](list-filter-urls.md): the redirect a submission of the
  form is answered with.
- [Overridable partials](overridable-partials.md): the partials a site package
  copies, and with them the handler text.
- [Dependency injection](dependency-injection.md): the listeners registered by
  attribute.
