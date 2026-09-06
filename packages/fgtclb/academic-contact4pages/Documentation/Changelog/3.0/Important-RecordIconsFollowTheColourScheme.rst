..  _important-contacts4pages-record-icons-follow-the-colour-scheme:

========================================================
Important: Record icons follow the backend colour scheme
========================================================

Description
===========

The record icons of this extension were registered with the core provider
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`, which renders
the default markup - the markup a :php:`typeicon_classes` entry reaches - as an
:html:`<img>` tag. An image is opaque to CSS, so the icon kept the ink of its
file whatever the backend colour scheme said, and a dark drawing stayed dark on
the dark cards of the record list.

They are now registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
which inlines the file in both markups, and the files themselves are drawn in
`currentColor` with no colour of their own.

The two identifiers are :php:`tx_academiccontacts4pages_domain_model_contact`
and :php:`tx_academiccontacts4pages_domain_model_role`.

Impact
======

The contact and role record icons take the text colour of the backend, so they
stay legible in a dark colour scheme. Their markup is now the inlined
:html:`<svg>` rather than an :html:`<img>`, which matters to any CSS or test
that addressed the image.

The content element icon :php:`academic_contacts4pages` keeps the core
provider, although it shares :file:`Extension.svg` with the contact record
icon. That file now carries :html:`fill="currentColor"` instead of the implicit
black, which renders identically inside an :html:`<img>`.

Affected Installations
======================

Every installation of this extension.

.. index:: Backend, ext:academic_contacts4pages
