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
    :guilabel:`EXT:academic_persons`. Two further constants of that extension
    are mapped the same way, because the partials rendering a contact read
    them: the image placeholder
    :typoscript:`{$plugin.tx_academicpersons.image.placeholder.default}` and
    the phone link prefix
    :typoscript:`{$plugin.tx_academicpersons.phoneNumbers.telPrefix}`.

    Nothing has to be done about it. The component names that extension's
    TypoScript in its own :file:`include_static_file.txt`, and both delivery
    mechanisms read that file, so the constant resolves whether this extension
    arrives through its site set or through its static template.

    The site set deliberately does *not* depend on a set of
    :guilabel:`EXT:academic_persons`. Such a dependency would not deliver the
    constant, and it would make that extension's content element selectable
    wherever this one is enabled.

..  _configuration-hidden-by-default:

The content element is hidden by default
========================================

:guilabel:`EXT:academic_contacts4pages` hides its content element for the whole
installation and brings it back per component. Whichever of the two mechanisms
below you use, it is what makes :guilabel:`Contact list` selectable in the
backend again — without one of them the content element is not offered, and
existing records keep rendering.

..  _site-set:

Include the site set
====================

Add the set to the :file:`config.yaml` of the site that should offer the content
element:

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
:sql:`sys_template` records, the same files are registered as static templates
and as selectable page TSconfig files.

..  tip::

    On TYPO3 v13 and v14 we recommend the site set — and if you use it, do not
    press the backend button :guilabel:`Create a root TypoScript record` on that
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

A site that uses the site set **and** the static template reads the shipped
files twice. The site set is applied before the :sql:`sys_template` record, so
the second read happens after the site settings and after
:file:`config/sites/<site>/constants.typoscript` — and it resets every constant
the extension ships a default for back to that default. For this extension
those are the three Fluid root paths of the plugin.

Nothing else is damaged: the :guilabel:`Constants` and :guilabel:`Setup` fields
of the :sql:`sys_template` record, the page TSconfig of a page and the page
TSconfig files selected on a page are all applied afterwards and still win. Use
one mechanism per site and the question does not arise.

..  _configuration-contact-list:

The contact list
================

..  _configuration-group-by-role:

Group by role
-------------

The :guilabel:`Configuration` tab of the content element offers
:guilabel:`Group by role` (:typoscript:`settings.groupByRole`), switched on by
default.

*   **On** – one heading per role, each followed by the contacts of that role,
    then the contacts without a role. A page on which no contact has a role
    renders one list without headings.
*   **Off** – all contacts in one list, in the order they are sorted on the
    page. A contact with a role shows its role name above its card, in an
    element with the class `academic-contacts4pages__role`.

A content element saved before the option existed stores no value for it and
reads the TypoScript default, so it keeps grouping:

..  code-block:: typoscript
    :caption: Shipped TypoScript setup

    plugin.tx_academiccontacts4pages.settings.groupByRole = 1

A value stored in the content element always wins over it.

..  _configuration-contact-item-partial:

The contact item partial
------------------------

:file:`Contacts/List.html` arranges the contacts and renders each of them
through :file:`Contacts/Item.html` of this extension — in the role groups,
below them and in the list that is not grouped. The grid column around the
partial belongs to the list template. By default the partial renders the
profile item of `EXT:academic_persons`.

To change the card of a contact, override this partial rather than the list
template:

..  code-block:: typoscript
    :caption: TypoScript setup

    plugin.tx_academiccontacts4pages.view.partialRootPaths.20 = EXT:my_sitepackage/Resources/Private/Extensions/academic_contacts4pages/Partials/

..  list-table::
    :header-rows: 1

    *   -   Argument
        -   Holds
    *   -   `contact`
        -   The contact.
    *   -   `role`
        -   The role of the contact, or nothing when it has none.
    *   -   `profile`
        -   The profile behind the contract of the contact.
    *   -   `contract`
        -   The contract the contact names.
    *   -   `settings`
        -   The plugin settings.
    *   -   `data`
        -   The content element record, as an array.
    *   -   `grouped`
        -   Set when the contact renders below the heading of its role. The
            default partial then renders the profile name one heading level
            lower and leaves out the role name, which the heading already
            shows.

The list template also receives `record`, the content element as a record
object. A project list template that renders the header partial of
`EXT:fluid_styled_content` itself needs it on TYPO3 v14 — which only makes
sense where the layout of the content element leaves the header out, as it is
rendered twice otherwise. See
:ref:`the changelog entry <important-contacts4pages-plugin-assigns-record-view-variable>`
for what such a template needs.
