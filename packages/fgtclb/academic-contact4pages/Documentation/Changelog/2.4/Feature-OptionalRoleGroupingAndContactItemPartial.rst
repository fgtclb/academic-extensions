..  _feature-1790316809:

===========================================================
Feature: Optional role grouping and one partial per contact
===========================================================

Description
===========

The :guilabel:`Contacts for this page` content element has a new option,
:guilabel:`Group by role` (:typoscript:`settings.groupByRole`), on the
:guilabel:`Configuration` tab. It is switched on by default:

*   **On** – the contacts are listed below a heading per role, followed by the
    contacts without a role. This is the output the content element always had.
*   **Off** – all contacts of the page are listed in one list, in the order the
    editor sorted them on the page. A contact with a role shows its role name
    above its card.

Every contact now renders through one partial of this extension,
:file:`Contacts/Item.html`, in the role groups, below them and in the list that
is not grouped. By default it renders the profile item of
`EXT:academic_persons` exactly as the list template did before, so the output
does not change. A project that wants its own contact card overrides this one
partial instead of the whole list template:

..  code-block:: typoscript
    :caption: TypoScript setup

    plugin.tx_academiccontacts4pages.view.partialRootPaths.20 = EXT:my_sitepackage/Resources/Private/Extensions/academic_contacts4pages/Partials/

The partial receives `contact`, `role`, `profile`, `contract`, `settings`,
`data` and `grouped`, which is set for a contact below the heading of its role.
The grid column around it stays in the list template. See
:ref:`the contact item partial <configuration-contact-item-partial>`.

Both concern the content element only. A page template that renders the
contacts through the data processor of this extension receives them as before
and uses neither the option nor the partial.

Impact
======

Content elements saved before the option existed store no value for it. They
read the default :typoscript:`plugin.tx_academiccontacts4pages.settings.groupByRole = 1`
of the shipped TypoScript and keep grouping. An installation that replaces the
shipped setup with a copy of its own adds that line to it, or its existing
content elements lose the grouping.

A project that replaced :file:`Contacts/List.html` keeps rendering its own
template, which does not know the option. Where the override exists only to
drop the grouping or to change the card, switch the option off or override
:file:`Contacts/Item.html` instead, and remove the copy of the list template.

A project that already ships a partial named :file:`Contacts/Item.html` in a
partial root path of this plugin now has it rendered for every contact. Check
for one before updating.

.. index:: Backend, FlexForm, Fluid, Frontend, Template, TypoScript, ext:academic_contacts4pages
