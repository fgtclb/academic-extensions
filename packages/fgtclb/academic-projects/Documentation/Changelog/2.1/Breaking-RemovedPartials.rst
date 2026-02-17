.. include:: /Includes.rst.txt

.. _breaking-1746705800:

==========================
Breaking: Removed partials
==========================

Description
===========

Some partials got removed as the templating structure has changed.


Impact
======

Those partials include:

* `Resources/Private/Layouts/Default.html`
* `Resources/Private/Partials/Categories.html`
* `Resources/Private/Partials/Project/Items.html`
* `Resources/Private/Partials/Project/SingleItem.html`


Affected Installations
======================

EXT:academic_partners installations overriding those partials.


Migration
=========

Adapt overrides accordingly to the new templating structure.

.. index:: Fluid, Frontend
