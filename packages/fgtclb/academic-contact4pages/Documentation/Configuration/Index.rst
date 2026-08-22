:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

This extension ships its frontend TypoScript and its backend page TSconfig in
two forms: as TYPO3 **site sets**, and as classic **static templates** plus
**page TSconfig files** that are selected on a page. Both forms read the very
same files, so they configure an installation identically.

Pick one of them per site and stay with it — see
:ref:`Do not combine both <one-mechanism-per-site>` for what happens otherwise.

..  note::

    Site sets arrived in TYPO3 v13.1 (Feature: #103437). On TYPO3 v12 the sets
    below do nothing at all — the files they name are never read there — so an
    installation on that version configures itself through the static templates
    and the page TSconfig files described further down, and has to do so for the
    content element to be offered at all.

..  _configuration-components:

What the sets contain
=====================

The extension ships one content element, so it ships one component set and one
aggregate set that depends on it.

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-contacts4pages-list`
        -   The :guilabel:`Contact list` content element: its TypoScript
            (`plugin.tx_academiccontacts4pages`), the data processor that
            assigns the contacts of a page to the page template, and the page
            TSconfig that makes the content element selectable in the backend.
    *   -   `fgtclb/academic-contacts4pages`
        -   Everything above. This is the set to use unless you deliberately
            want a subset.

Both depend on `fgtclb/academic-base-ctype-group`, the set of
:guilabel:`EXT:academic_base` that labels the content element group all academic
extensions sort their elements into.

..  note::

    The setup of this extension reads
    :typoscript:`{$plugin.tx_academicpersons.detailPid}` — a constant this
    extension does not declare and that belongs to
    :guilabel:`EXT:academic_persons`.

    Nothing has to be done about it. The component names that extension's
    TypoScript in its own :file:`include_static_file.txt`, and both delivery
    mechanisms read that file, so the constant resolves whether this extension
    arrives through its site set or through its static template.

    The site set deliberately does *not* depend on a set of
    :guilabel:`EXT:academic_persons`. Such a dependency would not deliver the
    constant, it does not exist on TYPO3 v12 at all, and it would make that
    extension's content element selectable wherever this one is enabled.

..  _configuration-hidden-by-default:

The content element is hidden by default
========================================

:guilabel:`EXT:academic_contacts4pages` hides its content element for the whole
installation and brings it back per component. Whichever of the two mechanisms
below you use, it is what makes :guilabel:`Contact list` selectable in the
backend again — without one of them the content element is not offered, and
existing records keep rendering.

On TYPO3 v12 only the page TSconfig file is a candidate: the site set is not
read there. On that version the same file also carries the entry of the new
content element wizard, which v12 builds from page TSconfig rather than from
TCA (the TCA based wizard arrived in v13.0, Feature: #102834).

..  _site-set:

Include the site set
====================

On TYPO3 v13, add the set to the :file:`config.yaml` of the site that should
offer the content element:

..  code-block:: diff
    :caption: config/sites/my-site/config.yaml (diff)

     base: 'https://example.com/'
     rootPageId: 1
    +dependencies:
    +  - fgtclb/academic-contacts4pages

See also `TYPO3 Explained, Using a site set as dependency in a site
<https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`__.

..  _static-templates:

Include static templates
========================

For an installation that still configures its frontend through
:sql:`sys_template` records — which on TYPO3 v12 is every installation — the
same files are registered as static templates and as selectable page TSconfig
files.

..  tip::

    On TYPO3 v13 we recommend the site set — and if you use it, do not press
    the backend button :guilabel:`Create a root TypoScript record` on that
    site. The :sql:`sys_template` record it creates carries the flag
    :guilabel:`Clear` for constants and setup, and that flag discards everything
    the site sets contributed. An installation that is already in that state
    gets its configuration back by selecting the static templates below in that
    very record.

..  _static-typoscript:

Include static TypoScript
-------------------------

Edit the :sql:`sys_template` record of the site root and add the entry to
:guilabel:`Include static (from extensions)`:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Contacts4Pages: Contact list (academic_contacts4pages)`
        -   The TypoScript of the :guilabel:`Contact list` content element.
    *   -   :guilabel:`Academic Contacts4Pages: All components (academic_contacts4pages)`
        -   Every component this extension ships, in one entry.

..  _static-pagetsconfig:

Include static page TSconfig
----------------------------

Edit the page record of the site root, tab :guilabel:`Resources`, field
:guilabel:`Page TSconfig`, and add the entry:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Contacts4Pages: Contact list (academic_contacts4pages)`
        -   Makes the :guilabel:`Contact list` content element selectable, and
            configures its entry in the new content element wizard.
    *   -   :guilabel:`Academic Contacts4Pages: All components (academic_contacts4pages)`
        -   Every component this extension ships, in one entry.

The setting is inherited by every page below the one it is set on.

..  _one-mechanism-per-site:

Do not combine both
===================

This can only happen on TYPO3 v13. A site that uses the site set **and** the
static template reads the shipped files twice. The site set is applied before
the :sql:`sys_template` record, so the second read happens after the site
settings and after :file:`config/sites/<site>/constants.typoscript` — and it
resets every constant the extension ships a default for back to that default.
For this extension those are the three Fluid root paths of the plugin.

Nothing else is damaged: the :guilabel:`Constants` and :guilabel:`Setup` fields
of the :sql:`sys_template` record, the page TSconfig of a page and the page
TSconfig files selected on a page are all applied afterwards and still win. Use
one mechanism per site and the question does not arise.
