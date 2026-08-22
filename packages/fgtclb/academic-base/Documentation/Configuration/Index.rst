:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

This extension ships no frontend TypoScript. What it does ship is the backend
page TSconfig that labels the content element group :guilabel:`Academic`, the
group every academic extension sorts its content elements into.

That page TSconfig is delivered in three ways, and the first one needs no
configuration at all:

*   :file:`Configuration/page.tsconfig` of this extension, which TYPO3 includes
    for the whole installation. The group is therefore labelled on every
    installation, whatever the site configuration says.
*   The site set :yaml:`fgtclb/academic-base-ctype-group`, for a site
    configuration that lists it — usually because another academic extension
    depends on it.
*   The page TSconfig file registered for the page field
    :guilabel:`Page TSconfig`.

All three read the same file, so nothing changes when more than one of them
applies.

..  note::

    Site sets arrived in TYPO3 v13.1 (Feature: #103437). On TYPO3 v12 the set
    below does nothing at all — the file it names is never read there — and the
    two remaining ways are the ones that apply. Nothing has to be configured
    for the group label on either version.

..  _configuration-components:

What the sets contain
=====================

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-base-ctype-group`
        -   The label of the content element group :guilabel:`Academic` in the
            new content element wizard.
    *   -   `fgtclb/academic-base`
        -   Everything above. This is the set to use unless you deliberately
            want a subset.

Every academic extension that ships content elements depends on
`fgtclb/academic-base-ctype-group`, so a site that includes one of those sets
gets this one with it and needs no entry of its own.

..  _configuration-hidden-by-default:

No content element is hidden by default
=======================================

This extension ships no content element of its own, so it hides none. The
content elements of the other academic extensions are hidden for the whole
installation and brought back per component by the extension that ships them —
see the :guilabel:`Configuration` chapter of that extension.

..  _site-set:

Include the site set
====================

On TYPO3 v13, add the set to the :file:`config.yaml` of the site:

..  code-block:: diff
    :caption: config/sites/my-site/config.yaml (diff)

     base: 'https://example.com/'
     rootPageId: 1
    +dependencies:
    +  - fgtclb/academic-base

See also `TYPO3 Explained, Using a site set as dependency in a site
<https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`__.

..  _static-templates:

Include static templates
========================

For an installation that configures itself through records rather than through
a site configuration — which on TYPO3 v12 is every installation — the same file
is registered as a selectable page TSconfig file.

..  _static-typoscript:

Include static TypoScript
-------------------------

This extension ships no TypoScript and therefore registers no static template.

..  _static-pagetsconfig:

Include static page TSconfig
----------------------------

Edit the page record of the site root, tab :guilabel:`Resources`, field
:guilabel:`Page TSconfig`, and add the entry:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Base: Content element group (academic_base)`
        -   The label of the content element group :guilabel:`Academic`.
    *   -   :guilabel:`Academic Base: All components (academic_base)`
        -   Every component this extension ships, in one entry.

The setting is inherited by every page below the one it is set on.

..  _one-mechanism-per-site:

Do not combine both
===================

For this extension both mechanisms assign the same two values, so combining
them is harmless. For the academic extensions that also ship TypoScript it is
not — see the :guilabel:`Configuration` chapter of the extension in question.
Use one mechanism per site.
