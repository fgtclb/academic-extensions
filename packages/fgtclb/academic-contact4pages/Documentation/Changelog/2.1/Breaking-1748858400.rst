.. include:: /Includes.rst.txt

.. _breaking-1748858400:

==========================
Breaking: Removed partials
==========================

Description
===========

Some partials got removed as the templating structure has changed.


Impact
======

Those partials include:

* `Resources/Private/Partials/Contacts/ContactWidget.html`
* `Resources/Private/Partials/Contacts/ContactWidgetAddress.html`

This extension now reuses the partials of the academic-persons extension.

Affected Installations
======================

EXT:academic_contact4pages installations overriding those partials.


Migration
=========

Adapt overrides accordingly to the partials provided by EXT:academic-persons.

.. index:: Fluid, Frontend
