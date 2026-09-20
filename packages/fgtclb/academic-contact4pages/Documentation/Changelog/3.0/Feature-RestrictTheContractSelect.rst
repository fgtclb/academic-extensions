..  _feature-restrict-the-contract-select:

=====================================
Feature: Restrict the contract select
=====================================

Description
===========

The :guilabel:`Contract` field of a page contact record offers every contract
of the installation, which in an installation with more than one site is every
site's contracts. Page TSconfig now restricts it to the pages the contracts of
that page tree are stored on:

..  code-block:: typoscript
    :caption: Page TSconfig of a site root

    TCEFORM.tx_academiccontacts4pages_domain_model_contact.contract.itemsProcFunc {
        storagePids = 42,84
        recursive = 1
    }

The items of the field are built by :composer:`fgtclb/academic-persons`, which
ships the setting and documents it, including the longer path of the second
field it serves — the :guilabel:`Selected contracts` of its content element.

Impact
======

The setting is opt-in: without it the select offers what it offered before. A
contract the contact record already references stays selectable wherever it is
stored, so saving a record never drops its contract.

..  index:: Backend, TSConfig, ext:academic_contacts4pages, ext:academic_persons
