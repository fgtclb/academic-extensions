..  _integrator:

===============
For integrators
===============

:guilabel:`academic_contacts4pages` ships one content element. Its page
describes what it renders, which situation it is meant for, and every option an
editor sets on it. What a site configures once, the TypoScript and the data
processor for page templates, is described in :ref:`configuration`.

The content element reads its options from
:typoscript:`plugin.tx_academiccontacts4pages.settings`. The values an editor
stores in the content element are merged over the TypoScript of the plugin, and
a stored value replaces the TypoScript value of the same name, an empty one
included. This extension lists no option in
:typoscript:`ignoreFlexFormSettingsIfEmpty`. Its TypoScript sets a value for
one option, :confval:`Group by role <contacts-list-group-by-role>`, which
reaches only a content element saved before the option existed.

..  list-table::
    :header-rows: 1
    :widths: 10 30 25 35

    *   -   Icon
        -   Content element
        -   CType
        -   Shows
    *   -   ..  figure:: /Images/Icons/contacts.svg
                :width: 32px
                :alt: Icon of the Person’s Contacts on the Page content element
        -   :ref:`Person’s Contacts on the Page <integrator-contacts-list>`
        -   ``academiccontacts4pages_list``
        -   The contact persons an editor assigned to the page the content
            element is on, grouped by their role.

The contacts of a page can also be rendered by the page template, outside the
content area, through the data processor `academic-page-contacts`, see
:ref:`configuration-page-contacts`.

..  toctree::
    :hidden:
    :titlesonly:

    ContactsList
