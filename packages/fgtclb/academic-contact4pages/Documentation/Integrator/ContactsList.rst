..  _integrator-contacts-list:

=============================
Person’s Contacts on the Page
=============================

..  image:: /Images/Icons/contacts.svg
    :width: 48px
    :alt: Icon of the Person’s Contacts on the Page content element

:CType:
    ``academiccontacts4pages_list``

:Plugin:
    `AcademicContacts4pages`, plugin `List`, action
    :php:`ContactsController::listAction()`

:Set:
    `fgtclb/academic-contacts4pages-list`, which the aggregate set
    `fgtclb/academic-contacts4pages` includes, see
    :ref:`configuration-components`

:guilabel:`Person’s Contacts on the Page` shows the contact persons of a page:
the dean's office on the page of a faculty, the participants on the page of a
research project, the student advisers on the page of a study programme. Each
contact is rendered as the card of a profile of :guilabel:`academic_persons`,
with the image, the name, the position and the e-mail addresses, phone numbers
and addresses of the contract the contact names. The contacts are grouped below
the names of their roles.

..  figure:: /Images/Screenshots/contacts-list-default.png
    :alt: The header Contacts, then the role heading Head of programme followed by a profile card with image, name, position, office hours and contact details, and the role heading Student advisory service followed by a second card.
    :class: with-border with-shadow

    The start of a contact list grouped by role: the first two role groups,
    each with its heading. The contacts without a role follow below the last
    group.

It is the element for contact persons in the content area of a page. A page
layout that shows them in a sidebar or a panel uses the data processor of
:ref:`configuration-page-contacts` instead, which reads the same contacts.

Where the contacts come from
============================

The editor assigns contacts in the tab :guilabel:`Contacts` of a page, or in the
tab :guilabel:`Linked pages` of a contract, see
:ref:`introduction-contact-records`. Each contact names a contract of a person,
an optional role, and which of the e-mail addresses, phone numbers and
addresses of the contract to display.

The content element shows the contacts of the page it is stored on, in the order
they are sorted on that page. Put it on the page that carries the contacts. A
content element shown on another page, through an :guilabel:`Insert records`
element for example, still shows the contacts of its own page. A contact is
left out while the contract or the profile behind it is not visible in the
frontend.

What it renders
===============

Each contact is rendered through the partial :file:`Contacts/Item.html` of
this extension, which renders the profile card of :guilabel:`academic_persons`,
see :ref:`configuration-contact-item-partial`. The name links to the profile
detail page that the constant
:typoscript:`plugin.tx_academicpersons.detailPid` of
:guilabel:`academic_persons` names. The setting
:typoscript:`plugin.tx_academicpersons.detailLink` does not reach this content
element, the names are always linked. The crop variant and the placeholders of
the image and the prefix of the phone links come from the constants of
:guilabel:`academic_persons` as well, see the note in
:ref:`configuration-components`.

The headings of the roles take their level from the header layout of the
content element. The element is cached with the page. Saving the contacts in
the form of their page clears the cache of that page.

Related chapters:

*   :ref:`styling` describes the markup. The extension ships no stylesheet.
*   :ref:`configuration-content-element-header` is for a site whose content
    element layout renders no header.

..  _integrator-contacts-list-configuration:

Settings
========

The tab :guilabel:`Configuration` of the content element holds two options.

..  confval:: Show hidden records
    :name: contacts-list-show-hidden-records
    :type: checkbox
    :Default: off

    On, the content element shows the contacts of the page that are hidden, and
    the hidden e-mail addresses, phone numbers and addresses of every contact it
    shows. Start and stop times, access restrictions and deleted records still
    apply, and a contact whose contract or profile is not visible is still left
    out.

    The page is cached with the hidden contacts, so every visitor sees them,
    whatever the preview settings of the backend are. Use it where a page
    shows what the person records keep hidden elsewhere, an internal phone
    number for example, which the contact selects as its phone number.

    The data processor for page templates has an option of the same name,
    which is independent of this one, see :ref:`configuration-page-contacts`.

..  _configuration-group-by-role:

..  confval:: Group by role
    :name: contacts-list-group-by-role
    :type: checkbox
    :Default: on

    Whether the contacts are grouped below the names of their roles.

    *   **On**: one heading per role, each followed by the contacts of that
        role, then the contacts without a role. The roles follow each other in
        the order in which they first occur among the contacts of the page. A
        page on which no contact has a role renders one list without headings.
    *   **Off**: all contacts in one list, in the order they are sorted on the
        page. A contact with a role shows its role name above its card, in an
        element with the class `ace-role`.

    Switch it off where the order of the contacts matters more than their
    roles, or where every contact has a role of its own and a heading per
    person would repeat the card.

    A content element saved before the option existed stores no value for it
    and reads the TypoScript default, so it keeps grouping:

    ..  code-block:: typoscript
        :caption: Shipped TypoScript setup

        plugin.tx_academiccontacts4pages.settings.groupByRole = 1

    A value stored in the content element always wins over it.

..  figure:: /Images/Screenshots/contacts-list-not-grouped.png
    :alt: Below the header Contacts, hidden ones included, two profile cards side by side, each with its role name above it.
    :class: with-border with-shadow

    The contacts without grouping: the role name stands above each card. The
    element of the development instance has
    :confval:`Show hidden records <contacts-list-show-hidden-records>` switched
    on as well, as its header says: one of the two contacts is hidden and is
    shown only because of that option.
