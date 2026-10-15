.. _important-ace-481-academic-contacts4pages-workspaces:

=====================================================================
Important: The address selects of a page contact follow the workspace
=====================================================================

Description
===========

Version 2.4 made the e-mail address, phone number and postal address selects
of a page contact relations to the address tables of
:guilabel:`academic_persons`, see
:ref:`important-ace-481-academic-contacts4pages`. Version 3.0 of
:guilabel:`academic_persons` makes these tables workspace aware, see
`Breaking: Person records are workspace aware
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Changelog/3.0/Breaking-PersonRecordsAreWorkspaceAware.html>`__
in its changelog.

The selects offer each address of the contract once, as the workspace of the
editor shows it, with the uid of its live record as the value. Publishing
keeps that uid, for a changed record and for a record created in the workspace
alike. A record of another workspace, or one deleted in the editor's
workspace, is not offered.

The first workspace edit of a contract versions its addresses and its page
contacts in one command. TYPO3 would then point the selection of the page
contact version at the version row of its address, which publishing deletes.
The extension keeps the selection on the live address instead, as it keeps
the selection of a translated page contact on the default language address.

Impact
======

A page contact edited in a workspace keeps the address it selects when the
workspace is published. Nothing changes for an installation that does not use
workspaces.

Affected Installations
======================

Installations that edit person records or page contacts in a workspace.

.. index:: Backend, TCA, ext:academic_contacts4pages
