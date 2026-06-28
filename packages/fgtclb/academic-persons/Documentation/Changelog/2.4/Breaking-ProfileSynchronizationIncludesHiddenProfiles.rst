.. include:: /Includes.rst.txt

.. _breaking-1782285582:

==============================================================
Breaking: Profile synchronization now includes hidden profiles
==============================================================

Description
===========

The profile synchronization (profile update command) previously skipped
frontend users whose profile was hidden. Such profiles were neither
selected by
:php:`\FGTCLB\AcademicPersons\Provider\FrontendUserProvider::getUsersWithProfileResult()`
(the automatic `hidden` restriction excluded them) nor resolved by
:php:`\FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository::findByFrontendUser()`.

The synchronization now processes hidden profiles as well and keeps
their data up to date, without changing their visibility.

Impact
======

A hidden profile that was relying on being skipped by the
synchronization is now updated again on the next synchronization run.
Its data (name, contact records, ...) is overwritten with the current
frontend user data, while the `hidden` state itself is kept untouched.

The frontend user visibility (`fe_users.disable`) and the deleted state
are still respected, only the profile `hidden` field is ignored for the
synchronization.

Affected Installations
======================

All installations using the profile synchronization
(`EXT:academic_persons` create/update profile commands) together with
manually hidden profiles.

Migration
=========

If a profile should be excluded from the synchronization, use the
dedicated `skip_sync` flag of the profile instead of hiding it. Hiding a
profile now only controls its frontend visibility, not whether it is
synchronized.

.. index:: CLI, Database, PHP, NotScanned
