.. include:: /Includes.rst.txt

.. _feature-1782285580:

====================================
Feature: Synchronize hidden profiles
====================================

Description
===========

The profile synchronization (profile update command,
:php:`\FGTCLB\AcademicPersons\Service\ProfileUpdateCommandService`) now
also keeps **hidden profiles** up to date. Previously a frontend user
whose profile was hidden was excluded from the synchronization
completely, so the profile was never updated and no synchronization
events were dispatched for it.

The :php:`\FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository`
provides a new method that returns profiles regardless of their
visibility:

* :php:`findByFrontendUserIncludingHidden(int $frontendUserUid): QueryResultInterface`

It is used by the synchronization, while the regular
:php:`findByFrontendUser()` keeps respecting the visibility and is still
used for the frontend display.

The visibility itself is never changed by the synchronization — that
stays the responsibility of the :php:`\FGTCLB\AcademicPersons\Profile\ProfileFactoryInterface`
implementation — so a manually hidden profile stays hidden while its data
is kept in sync.

The profile create command keeps skipping frontend users that already
have a profile, including a hidden one, so no duplicate profiles are
created for them.

..  note::

    This changes the behaviour of the profile synchronization and is
    therefore breaking. See :ref:`breaking-1782285582` for details and
    the migration.

Impact
======

Hidden profiles are no longer silently excluded from the
synchronization. To exclude a profile from synchronization, use the
dedicated `skip_sync` flag of the profile instead of hiding it.

Affected Installations
======================

All installations using the profile synchronization
(`EXT:academic_persons` create/update profile commands) starting with
version 2.4.

.. index:: CLI, Database, PHP, Frontend
