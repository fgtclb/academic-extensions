..  _developers:

==============
For developers
==============

The contacts content element and the data processor that hands the contacts of
a page to a page template read the contacts through one service, and that
service dispatches one PSR-14 event. It is the supported way to change which
contacts a page shows, in either output, without copying the controller. The
content element and the backend form of a contact dispatch two events of
:guilabel:`academic_base` as well.

Which classes of this extension are public API, and what that promises, is
stated for all academic extensions on the :ref:`extension points page of
academic_base <fgtclb/academic-base:developers-extension-points>`.

..  _developers-events:

Events
======

..  list-table::
    :header-rows: 1
    :widths: 25 30 30 15

    *   -   Event
        -   Dispatched
        -   What a listener can change
        -   Owner
    *   -   :ref:`ModifyPageContactsEvent <developers-event-modify-page-contacts-event>`
        -   each time the content element renders and each time the data
            processor runs, after the contacts of the page are read and before
            they are grouped by role
        -   replace the contacts
        -   this extension
    *   -   :ref:`ModifyPluginViewEvent <developers-event-modify-plugin-view-event>`
        -   each time the content element renders, after it assigned its view
            variables
        -   assign further view variables
        -   :guilabel:`academic_base`
    *   -   :ref:`ModifyTcaSelectFieldItemsEvent <developers-event-modify-tca-select-field-items-event>`
        -   in the backend form of a contact, when the items of the e-mail
            address, phone number and address selects are built
        -   change, remove, reorder or add items
        -   :guilabel:`academic_base`

Nothing changes in an installation that has no listener.

..  toctree::
    :hidden:
    :titlesonly:

    Events/ModifyPageContactsEvent
    Events/ModifyPluginViewEvent
    Events/ModifyTcaSelectFieldItemsEvent

..  index:: Frontend, Backend, PHP-API, ext:academic_contacts4pages
