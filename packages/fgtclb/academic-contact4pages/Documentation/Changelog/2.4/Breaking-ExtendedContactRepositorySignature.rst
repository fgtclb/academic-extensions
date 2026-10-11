.. _breaking-ace-250-academic-contacts4pages:

=============================================================
Breaking: Extended `ContactRepository::findByPid()` signature
=============================================================

Description
===========

To support the new :guilabel:`Show hidden records` plugin option,
:php:`\FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository`
changed one method:

*   :php:`findByPid(int $pid, bool $showHidden = false): QueryResultInterface`.
    The optional parameter :php:`$showHidden` was appended.

With :php:`$showHidden` set to :php:`true`, the query ignores only the hidden
flag of the contacts. Deleted records, start and end time and frontend user
groups still apply. The contacts content element passes its
:typoscript:`settings.showHiddenRecords`.

Impact
======

Calls of the repository keep working: the new parameter has a default value.

A class that extends :php:`ContactRepository` and overrides :php:`findByPid()`
with the old signature fails with a PHP fatal error, because the method of the
subclass no longer accepts every parameter of the parent method.

Affected Installations
======================

Installations that extend
:php:`\FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository` and
override :php:`findByPid()`. All other installations are unaffected.

Migration
=========

Add the parameter to the overriding method and pass it on:

..  code-block:: php

    public function findByPid(int $pid, bool $showHidden = false): QueryResultInterface
    {
        // ...
        return parent::findByPid($pid, $showHidden);
    }

.. index:: PHP-API, ext:academic_contacts4pages
