# Page type rendering

`academic_programs`, `academic_partners` and `academic_projects` each register a
page type with a backend layout and ship a page template for it. The site
package owns the page object, `page.10`, and the extension refines it inside a
condition on its page type with, among others, the template name, the
template paths and a data processor that turns the page record into the
program, partner or project the template renders.

## Two page object types

A site package renders its pages with a `FLUIDTEMPLATE` or, from TYPO3 v13 on,
a `PAGEVIEW` page object. They hand the page record to the template and to its
data processors under different names:

| Variable | `FLUIDTEMPLATE` (v12, v13) | `PAGEVIEW` (v13 only)                                               |
|----------|----------------------------|---------------------------------------------------------------------|
| `data`   | the page record            | not assigned                                                        |
| `page`   | not assigned               | the page information object, `{page.pageRecord}` is the page record |

`PAGEVIEW` reserves `site`, `language` and `page` for its own variables. A
`FLUIDTEMPLATE` reserves `data` and `current`. Every other name is free, so a
`PAGEVIEW` site package may assign a `data` of its own, and a `FLUIDTEMPLATE`
site package a `page` of its own.

## Where the page record comes from

The three page data processors, `program-data`, `partner-data` and
`project-data`, take the record of `page` when it is an object with
`getPageRecord()`, and `data` otherwise. A value that is not a non-empty array
adds nothing. The project page template resolves its heading fallback in the
same order: `{page.pageRecord}`, then `{data}`.

Up to ACE-788 they read `data` first. On TYPO3 v13 a `PAGEVIEW` site package
that assigned a text as `data` made every page of these types fail with a type
error, and the records of a query were read as if they were the page record.
Checking that `data` is an array would not be enough for that reason.

On `main` the processors check `page` with `instanceof` against the class of the
page information object. That class does not exist on TYPO3 v12, and `phpstan`
analyses this branch against v12 as well, where an `instanceof` against a
missing class is an error. This branch therefore asks for the method, see
[Core version aware code](core-version-aware-code.md#a-check-for-the-api-instead-of-the-version).

## Tests

The page template test of each of the three extensions renders a page with
a `PAGEVIEW` site package that assigns a text or the records of a query as
`data` (v13 only, `not-core-12`), and with a `FLUIDTEMPLATE` site package that
assigns a text as `page` (both versions). The project test also renders a
project without a project title on `PAGEVIEW` and on `FLUIDTEMPLATE`, whose
heading is the title of the page.

## See also

- [Core version aware code](core-version-aware-code.md) - the version
  differences and how they are expressed on this branch.
- [TypoScript and site sets](typoscript-and-site-sets.md) - how the page type
  TypoScript reaches a site.
- [Functional tests](../testing/functional-tests.md) - the harness the page
  template tests run in.
