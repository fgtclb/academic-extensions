.. _important-1788197605:

=============================================================
Important: findByPid() returns each contact once per language
=============================================================

Description
===========

:php:`\FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository::findByPid()`
used to lift ``respectSysLanguage`` while matching ``page = <uid>`` across all
language rows. Under a translated language a contact with a translation matched
twice - once through its default-language record and once through the
translation - and rendered twice in the contacts plugin. Language-only records
leaked into the default language, and TYPO3 v13 and v14 disagreed about which
rows survived the overlay.

The method now resolves the matching rows per language first - records of the
requested language (connected translations and language-only records alike),
default-language records without a translation in that language, and records
in language "all" - and then fetches exactly those rows, with the overlay
pinned to ``OVERLAYS_MIXED`` so a translated row maps onto its default
record's identity. The behaviour is identical on TYPO3 v13 and v14 and
independent of the site's fallback configuration.

Impact
======

Each contact arrives exactly once per language context:

- a default-language contact without a translation is returned as-is,
- a translated contact is returned once, represented by its translation,
- legacy duplicated contact translations already present in the database
  (created by the former synchronization recursion) collapse to a single
  result row without any database cleanup,
- language-only contacts no longer appear in the default language, and
  languages without any translation no longer receive foreign-language rows,
- ``count()`` on the result now agrees with the number of iterated objects.

The hidden-record handling is unchanged: ``$showHidden`` continues to lift
only the ``disabled`` enable field.

Affected Installations
======================

Every installation rendering the contacts plugin or data processor on a site
with more than one language - in particular installations whose database still
holds duplicated contact translations from the former synchronization.

.. index:: Frontend, Database, ext:academic_contacts4pages
