.. _important-ace-857-academic-contacts4pages:

=================================================
Important: Hidden contacts show their translation
=================================================

Description
===========

With the plugin option :guilabel:`Show hidden records`, the
:guilabel:`Person’s Contacts on the Page` content element
(:php:`academiccontacts4pages_list`) lists hidden contacts as well.

In version 2.4 the hidden flag was lifted through the query settings only.
Extbase looks up the translation of a record through the page repository of
TYPO3, which on TYPO3 v13 follows the visibility settings of the context, so
the hidden translation was not found. On a translated page a hidden contact was
listed with the values of its default language record.

The contacts are now fetched with the visibility lifted for the lookup of the
translation as well, and a hidden contact is listed with its translation, like a
visible one. Only that lookup sees hidden records, the profile and the contract
of a contact keep their own visibility. TYPO3 v14 finds the hidden translation
on its own.

Impact
======

On a translated page, a hidden contact shows its translation where it showed
its default language values. Without the option nothing changes.

Affected Installations
======================

Installations on TYPO3 v13 with translated pages that enable
:guilabel:`Show hidden records` on the content element.

.. index:: Frontend, ext:academic_contacts4pages
