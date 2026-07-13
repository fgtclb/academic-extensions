.. _important-ace-250-academic-contacts4pages:

==============================================================
Important: Extended `ContactRepository::findByPid()` signature
==============================================================

Description
===========

To support the new "Show hidden records" plugin option, the
:php:`\FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository`
gained an extended method:

* :php:`findByPid(int $pid, bool $showHidden = false): QueryResultInterface`
  — the new optional :php:`$showHidden` parameter was appended.

When :php:`$showHidden` is :php:`true`, the query ignores only the
`disabled` (`hidden`) enable field via the Extbase query settings. The
:php:`\FGTCLB\AcademicContacts4pages\Controller\ContactsController` reads
:php:`$this->settings['showHiddenRecords']` and passes it to the
repository.

Impact
======

The change is non-breaking: the new parameter has a default value, so
existing calls keep working unchanged. Projects that extend or replace
:php:`ContactRepository` should adopt the same signature when overriding
:php:`findByPid()`.

Affected Installations
======================

Only installations that extend or override
:php:`\FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository`
need to take the extended signature into account. All other installations
are unaffected.

.. index:: PHP, ext:academic_contacts4pages
