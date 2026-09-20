.. _important-contract-and-role-sort-their-contacts:

========================================================================
Important: Contracts and contacts roles sort their contacts on their own
========================================================================

Description
===========

A page contact is an inline child of three records at once: of its page, of the
contract it belongs to, and of its contacts role. All three relations wrote the
same :sql:`sorting` column.

:php:`RelationHandler::writeForeignField()` numbers the children of the record
being saved 1..n in the order of its form, so saving a contract or a contacts
role renumbered its contacts *across every page that owns one of them*. An
editor who had arranged the contacts on a page saw that arrangement replaced by
the order of an unrelated form - in the backend and, since a page renders its
contacts in :sql:`sorting` order, in the frontend as well.

The contract relation and the contacts role relation now have sort columns of
their own, :sql:`tx_academiccontacts4pages_domain_model_contact.contract_sorting`
and :sql:`role_sorting` of the same table, and order by them. The page keeps
:sql:`sorting`, so nothing that renders today changes.

Impact
======

Saving a contract or a contacts role no longer changes the order of any page,
and a page no longer changes the order those forms show. All three
arrangements are kept side by side.

A contact that joins a contract or a contacts role afterwards is appended to
the end of that record's list, whether it was created there, given the relation
in the contact form on a page, copied or localized. Changing the relation moves
it to the end of the new one; clearing it removes the contact from that list
altogether.

The new columns are added by :file:`ext_tables.sql`: **run the database
analyzer once after updating**. Every contact that existed before carries
:sql:`0` in them, so the upgrade wizard *Seed the contact sort order of
contracts and contacts roles* fills them with the order those forms show today
- the current :sql:`sorting` of each contact, with :sql:`uid` settling ties.
Run it once; it reports nothing to do afterwards. Until it has run, a contact saved in the backend keeps no position of its own:
the wizard is the one that knows the order the contracts and contacts roles show today, so a contact
whose list is still unseeded is left to it. Running the wizard again is
harmless:
it appends what has no position yet and never renumbers what has one, so an
arrangement made in one of those forms is not reset by it.

Affected Installations
======================

Every installation of this extension that assigns contacts to contracts or to
contacts roles.

.. index:: Backend, Database, TCA, ext:academic_contacts4pages
