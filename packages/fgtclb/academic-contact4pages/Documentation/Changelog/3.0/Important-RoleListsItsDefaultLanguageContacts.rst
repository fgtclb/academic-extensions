.. _important-ace-943-academic-contacts4pages:

============================================================
Important: Roles list their contacts of the default language
============================================================

Description
===========

TYPO3 14.3.10 gives every inline child the language of its parent when the
parent is created, changes its language or gets a new child, see
`TYPO3 issue 103970 <https://forge.typo3.org/issues/103970>`__. The core
expects an inline child to have exactly one parent.

A page contact has more than one. Its contacts role is a select with
:php:`'l10n_mode' => 'exclude'`, so a translated page contact points at the
default language record there, and the inline list
:php:`tx_academiccontacts4pages_domain_model_role.contacts` of that record held
the translations as well. Adding a page contact to that list on TYPO3 14.3.10
therefore turned every translated page contact of the record into a page
contact of the default language, and reset its translation parent and source.

The list holds the page contacts of the default language only now, through
:php:`'foreign_match_fields' => ['sys_language_uid' => 0]`.

Impact
======

The backend form of the record lists the page contacts of the default language
only. The translations keep their language and their translation parent when a
page contact is added to the list, on TYPO3 13 as on TYPO3 14.

Affected Installations
======================

Installations on TYPO3 14.3.10 or later with translated page contacts. Nothing has
to be done.

.. index:: Backend, TCA, ext:academic_contacts4pages
