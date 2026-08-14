..  _feature-dedicated-address-record-selection:

===============================================
Feature: Dedicated selection of address records
===============================================

Description
===========

A contact record (:sql:`tx_academiccontacts4pages_domain_model_contact`) now
carries three additional selects, grouped in the palette
:guilabel:`Displayed address records`:

*   :guilabel:`E-mail address` (:sql:`email_address`)
*   :guilabel:`Phone number` (:sql:`phone_number`)
*   :guilabel:`Physical address` (:sql:`physical_address`)

They are offered as soon as a contract is selected and list the email
addresses, phone numbers and physical addresses of exactly that contract. The
contract field reloads the form on change, so the selects always match the
contract that is currently selected.

Each select offers two options besides the address records themselves:

:guilabel:`Display all`
    The default. All address records of that kind are rendered, the behaviour
    of every contact record created so far.

:guilabel:`Do not display`
    The address record kind is left out of the frontend output for this
    contact entirely.

The narrowing is applied by the contact record itself, so it takes effect in
the :guilabel:`Contacts for this page` plugin, in the page based rendering
through :php:`ContactsProcessor` and in project templates built on either of
them - they all reach the address records through
:php:`Contact::getContract()`. :php:`Contact::getUnfilteredContract()` returns
the contract with all of its address records.

Only default language records are offered: the selection is shared by all
translations of a contact, and the frontend resolves the language overlay of
the address records itself. Deleted records are not offered. Hidden ones are,
marked with a :guilabel:`[Hidden]` prefix - whether they reach the frontend is
decided by the plugin, see below.

Impact
======

Editors can restrict a page contact to a single email address, phone number
and physical address of the contract, or suppress a kind completely - for
example to publish the office phone number of a person on one page and the
private one on another, from the same contract.

Hidden address records reach the frontend only through the
:guilabel:`Contacts for this page` plugin and only while its
:guilabel:`Show hidden records` option (:typoscript:`settings.showHiddenRecords`)
is enabled, the same option that decides about hidden contacts. That covers both
ways of displaying them: with the option enabled a hidden record is part of
:guilabel:`Display all` and can be selected as the single one to display.
Everywhere else - the page based rendering through :php:`ContactsProcessor`
included - hidden records are left out, and a selection pointing at one behaves
like :guilabel:`Do not display`.

A selection that cannot be resolved at all behaves the same way and renders
nothing. This happens when the contract of a contact is switched after the
selection was made, or when the selected address record is deleted afterwards.
There is deliberately no fallback to :guilabel:`Display all`: publishing the
private phone number of a person because a record was removed is worse than
publishing nothing.

Affected Installations
======================

All installations using the `EXT:academic_contacts4pages` extension starting
with version 2.4. The three new columns default to :guilabel:`Display all`, so
existing contact records keep their current frontend output and no migration
is required. A database compare has to be applied for the new columns.

.. index:: Backend, Frontend, Database, ext:academic_contacts4pages
