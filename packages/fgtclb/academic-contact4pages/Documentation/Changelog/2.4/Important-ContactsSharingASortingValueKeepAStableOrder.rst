.. _important-contacts-sharing-a-sorting-value-keep-a-stable-order:

===============================================================
Important: Contacts sharing a sorting value keep a stable order
===============================================================

Description
===========

The contacts of a page were ordered by :sql:`sorting` alone. Contacts sharing
a value were therefore returned in whatever order the database chose, and
PostgreSQL, which promises no order without an :sql:`ORDER BY`, can hand them
back differently from one request to the next.

The page form does not produce such a tie, it renumbers the contacts of a
page on every save. Ties come from:

*   contacts of one page kept in different storage folders, because a new
    record is numbered within its folder while the query ignores the folder a
    contact lives in,
*   contacts copied along with a contract or a contacts role, which keep the
    value they had,
*   imports,
*   contacts written before the sort columns of
    :ref:`important-contract-and-role-sort-their-contacts` existed, when saving
    a contract or a contacts role renumbered :sql:`sorting` across pages.

:php:`\FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository::findByPid()`
now orders by :sql:`sorting` with :sql:`uid` settling ties.

Impact
======

The contacts content element and the page contacts data processor show the
same list on every request. Contacts with distinct :sql:`sorting` values keep
the order an editor arranged. Only the relative order within a tie is pinned.
SQLite, MySQL and MariaDB return such a tie in :sql:`uid` order in practice, so
installations on them are not expected to see a change. Nothing in those
databases promises that order, which is why it is pinned now. TYPO3 v12 and v13
behave alike here.

Affected Installations
======================

Every installation of this extension, observably those on PostgreSQL.

.. index:: Database, Frontend, ext:academic_contacts4pages
