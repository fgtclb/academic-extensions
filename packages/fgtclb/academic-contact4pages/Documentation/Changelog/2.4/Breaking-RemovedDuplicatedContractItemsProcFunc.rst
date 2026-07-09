.. _breaking-1783613369:

==============================================================================
Breaking: Removed duplicated `ContractItemsProcFunc` in `EXT:academic_contacts4pages`
==============================================================================

Description
===========

`EXT:academic_contacts4pages` shipped its own
:php:`\FGTCLB\AcademicContacts4pages\Backend\FormEngine\ContractItemsProcFunc`,
which basically duplicated the contract `itemsProcFunc` of the hard-depended
`EXT:academic_persons`.

The duplicated class has been removed. The contract selection field now uses
the `itemsProcFunc` shipped by `EXT:academic_persons`,
:php:`\FGTCLB\AcademicPersons\Backend\FormEngine\ContractItems`, which is
easier to maintain, especially when changes to the `itemsProcFunc` are made.

Impact
======

Referencing the removed class throws a PHP error. The shipped TCA of
`tx_academiccontacts4pages_domain_model_contact` has been switched to the
`EXT:academic_persons` handler.

Affected Installations
======================

Installations that reference
:php:`\FGTCLB\AcademicContacts4pages\Backend\FormEngine\ContractItemsProcFunc`
in own TCA/FlexForm configuration, extend or replace it, or call it directly.

Migration
=========

Use the `itemsProcFunc` provided by `EXT:academic_persons` instead:

.. code-block:: text

    FGTCLB\AcademicContacts4pages\Backend\FormEngine\ContractItemsProcFunc->itemsProcFunc
    => FGTCLB\AcademicPersons\Backend\FormEngine\ContractItems->itemsProcFunc

Projects that need to adjust the available contract items should use the
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` event listener
instead of a custom `itemsProcFunc`.

.. index:: Backend, PHP, TCA, ext:academic_contacts4pages
