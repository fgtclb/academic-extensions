.. _important-1788197604:

===========================================================
Important: Contacts of untranslated pages stay untranslated
===========================================================

Description
===========

A contact record points at a page through its ``page`` column - a plain group
relation holding the default-language page uid, which the TYPO3 ``DataHandler``
copies verbatim when a contact is localized. Localizing a contract or a profile
cascades into the contacts below it, so every localization - a plain backend
"Translate", the translation synchronization of EXT:academic_persons, or an
inline synchronize - used to create translated contacts pointing at pages that
do not exist in the target language. Such a translated contact carries no
content of its own (every column except ``page`` is ``l10n_mode=exclude``) and
made the contact appear twice on the page.

The extension now registers a ``DataHandler`` hook
(:php:`\FGTCLB\AcademicContacts4pages\Hook\DataHandlerHooks`) that removes a
freshly localized contact again when the page it points at has no translation
in the contact's language. The check is workspace aware: a page translated only
in the acting workspace counts as translated there, and a contact localized in
a workspace is discarded rather than deleted, so nothing leaks into the live
state. A contact whose page *is* translated keeps its translation, still
pointing at the default-language page uid - that is how TYPO3 models page
references.

Impact
======

Localizing a contract, profile or contact no longer produces contact
translations for pages that are not translated into the target language. In
the live workspace the removal is a regular soft delete (the row remains in
the database with ``deleted=1`` and can be restored through the recycler); in
a workspace the new record is discarded entirely.

The guard only acts on connected translations. Contacts copied to a language
without a connection to a default-language record (free mode,
``copyToLanguage``) are left alone, as are contacts created directly in a
target language.

Translated contacts that already exist in the database are not touched by the
hook. They stop rendering as duplicates because
:php:`ContactRepository::findByPid()` now resolves each contact exactly once
per language - see the companion changelog entry. Installations that want to
clean such legacy rows up can delete the translated contact records whose
``page`` has no translation in their language; no upgrade wizard ships for
this yet.

Affected Installations
======================

Every installation that localizes contracts, profiles or contacts into
languages in which the referenced pages are not (all) translated - through the
backend or through the translation synchronization of EXT:academic_persons.

.. index:: Backend, Database, ext:academic_contacts4pages
