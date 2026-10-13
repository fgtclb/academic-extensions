.. _important-ace-481-academic-contacts4pages:

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

Before, the selects worked on bare uids:

*   A copied contract, or a copied profile with its contracts, left its page
    contacts selecting the addresses of the original.
*   The reference index knew nothing of a selection.
*   A selection that matched none of the offered addresses, for example after
    the contract of the contact was switched, was dropped from the form and
    written away on the next save.

A copy of a contract or of a profile now selects the copies of the selected
addresses, and a translation of a contract keeps its page contacts on the
default language addresses. The extension does this in a :php:`DataHandler`
hook rather than leaving it to TYPO3, which would also remap the selection of
a translated page contact to the translated address. The fields name their
address table in :php:`dontRemapTablesOnCopy` for that reason.

The selects still offer each address of the contract once, and the editor's
access to the folder of the person records does not narrow them, just as it
does not narrow the contract select.

Impact
======

Stored values keep their meaning, and neither the database schema nor an
upgrade wizard is involved.

A selection that matches none of the offered addresses stays stored. The form
shows it with the "missing label" marker of TYPO3, and a save that does not
touch the select keeps it.

The reference index gets a row for every selected address, and one with the
uid :php:`-1` for "display none", as for the :php:`-1` and :php:`-2` of a
frontend user group selection. Existing page contacts get their rows on their
next save, or at once with:

..  code-block:: bash

    vendor/bin/typo3 referenceindex:update

A listener of :php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent`
still receives the final item list, after the extension replaced the record
items TYPO3 added.

Affected Installations
======================

Every installation that uses the address selects of page contacts, and in
particular those that copy contracts or profiles or translate contracts.

.. index:: Backend, TCA, ext:academic_contacts4pages
