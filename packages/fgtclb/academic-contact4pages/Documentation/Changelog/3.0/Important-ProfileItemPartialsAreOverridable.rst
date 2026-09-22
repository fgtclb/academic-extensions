.. _important-profile-item-partials-are-overridable:

========================================================
Important: The profile item is built from small partials
========================================================

Description
===========

The contacts of a page are rendered through the :file:`Profile/Item` partial of
`EXT:academic_persons`. That partial no longer holds the detail link, the name,
the contracts and the image in one file: each of them is a partial of its own
now, so a project changes one of them without copying the item. The partials and
their arguments are described in the :guilabel:`Templates` chapter of
`EXT:academic_persons`.

Two consequences reach this extension.

The academic title is part of the name
--------------------------------------

A contact whose profile carries a :guilabel:`Title` shows it in front of the
name - `Prof. Dr. Anna Beispiel` rather than `Anna Beispiel`. It is the same
change every persons element shows.

The name is also joined differently: it used to be written with literal spaces,
so a contact whose profile has no middle name carried two of them in the markup.
An empty part is now left out with its separator.

An override has to be registered here as well
---------------------------------------------

This plugin resolves the persons partials through partial root paths of its own
(`plugin.tx_academiccontacts4pages.view.partialRootPath`, key `10`), so an
override registered for `plugin.tx_academicpersons` alone does not reach it.
Register the same directory twice:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicpersons.view.partialRootPath = EXT:my_sitepackage/Resources/Private/Extensions/academic_persons/Partials/
    plugin.tx_academiccontacts4pages.view.partialRootPath = EXT:my_sitepackage/Resources/Private/Extensions/academic_persons/Partials/

That has always applied to a copy of :file:`Profile/Item.html`. It is repeated
here because a small override makes it easy to forget the second line.

Impact
======

The heading of every contact carries the class
`academic-persons-item__name` next to the `card-title` it had, and the image
carries `academic-persons-item__image` next to its existing classes. No class
was removed.

Affected Installations
======================

Every installation that shows contacts, through the content element or through
the data processor. An installation that copied :file:`Profile/Item.html` into
its own partial root path renders exactly what it rendered before, the academic
title included - and should drop the copy in favour of the small partial it
actually wanted to change.

.. index:: Fluid, Frontend, Template, ext:academic_contacts4pages
