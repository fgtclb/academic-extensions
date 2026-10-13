..  _important-ace-430-academic-contacts4pages:

======================================================
Important: Each page contact renders once per language
======================================================

Description
===========

The contacts of a page render each contact once in every language, on
TYPO3 v12 and v13 alike. A contact renders as its translation where it has
one, and as its default language record where it has none. A contact that
exists in one language only renders in that language, and one for all
languages renders in every language.

Before, every translation of a contact rendered next to its default record,
in the default language and in every translated one, and a contact of one
language only rendered in all of them.

Hidden contacts render when the content element shows hidden records, as
before, and in a frontend preview that shows hidden records.

Impact
======

Nothing has to be cleaned up. Duplicated translations of a contact stay in the
database and stop rendering twice. A contact with several translations in one
language renders once, as one of them. Which one is up to the database.

Templates that count the contacts of a page, with :html:`f:count()` for
example, now count each contact once, as the list shows it.

Affected Installations
======================

Installations with translated page contacts.

..  index:: Frontend, ext:academic_contacts4pages
