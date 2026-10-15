.. _important-address-selections-are-relations:

==============================================================
Important: The address selects of a page contact are relations
==============================================================

Description
===========

The e-mail address, phone number and postal address selects of a page contact
choose one address record of its contract, or "display all" (:php:`0`) or
"display none" (:php:`-1`). They now declare the address table of
:php:`academic_persons` as their :php:`foreign_table`, limited to the records
of the contact's contract. TYPO3 therefore knows the stored values as
relations.

Before, the selects worked on bare uids, and three things went wrong:

*   Since the address tables are workspace aware, the selects offered every
    record of the contract in every workspace. An address changed in a
    workspace was offered twice under the same label, once as its live record
    and once as its version row, and an address created in another workspace
    was offered as well. A selected version row never matched in the frontend
    and was deleted when the workspace was published, so the contact showed no
    address of that kind.
*   A copied contract, or a copied profile with its contracts, left its page
    contacts selecting the addresses of the original.
*   The reference index knew nothing of a selection.

The selects now offer each address of the contract once, as the workspace of
the editor shows it, with the uid of its live record as the value. Publishing
keeps that uid, for a changed record and for a record created in the workspace
alike. A record of another workspace, or one deleted in the editor's
workspace, is not offered. The editor's access to the folder of the person
records does not narrow the items, just as it does not narrow the contract
select.

A copy of a contract or of a profile selects the copies of the selected
addresses. The extension does this in a :php:`DataHandler` hook rather than
leaving it to TYPO3, which would also remap a selection when a workspace edit
of the contract versions its addresses, and so point it at a version row
again. The fields name their address table in :php:`dontRemapTablesOnCopy` for
that reason.

Impact
======

Stored values keep their meaning, and neither the database schema nor an
upgrade wizard is involved.

A selection that matches none of the offered addresses, for example after the
contract of the contact was switched, stays stored. The form shows it with the
"missing label" marker of TYPO3 instead of dropping it, and a save that does
not touch the select keeps it. Before, the next save wrote it away. A
selection of a version row, which only a 3.0 development installation can
hold, shows up the same way and has to be picked again.

TYPO3 v14 removes a select with a :php:`foreign_table` from the form when none
of its items is a record. The three selects set :php:`showIfEmpty`, so they
stay visible with "display all" and "display none" when the contract has no
address of that kind, as on TYPO3 v13.

The reference index gets a row for every selected address, and one with the
uid :php:`-1` for "display none", as for the :php:`-1` and :php:`-2` of a
frontend user group selection. Existing page contacts get their rows on their
next save, or at once with:

..  code-block:: bash

    vendor/bin/typo3 referenceindex:update

A listener of :php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent`
still receives the final item list, after the extension replaced the record
items TYPO3 added, see :ref:`developers-event-modify-tca-select-field-items-event`.

Affected Installations
======================

Every installation that uses the address selects of page contacts, and in
particular those that edit person records in workspaces or copy contracts or
profiles.

.. index:: Backend, TCA, ext:academic_contacts4pages
