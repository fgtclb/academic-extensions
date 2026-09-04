..  _feature-configurable-frontend-user-phone-number-types:

======================================================
Feature: Configurable frontend user phone number types
======================================================

Description
===========

Telephone and fax numbers imported from frontend users can now use separate
phone-number types. The new extension configuration options
:confval:`profile.fe_users.telephoneNumberType` and
:confval:`profile.fe_users.faxNumberType` both default to ``business``. A
configured value that is not present in
:confval:`types.phoneNumberTypes` falls back to the valid undefined type
``''``.

Imported telephone numbers now use the stable import identifier
``telephone:fe_users:<uid>``. Fax numbers keep
``fax:fe_users:<uid>``. Synchronisation preserves an existing selectable type
and only corrects the historical invalid values ``phone`` and ``fax``.

Impact
======

New imports use the configured types. Existing legacy telephone records with
``phone:fe_users:<uid>`` are reused and normalized during synchronisation, so
skipping the upgrade wizard does not create duplicates. If both legacy and
canonical records exist on one contract, the canonical record wins and the
legacy record is left untouched.

Run the :guilabel:`Migrate imported frontend-user phone number types` upgrade
wizard to migrate existing records in bulk. The wizard includes hidden and
deleted records, preserves valid editor-selected types, and does not merge or
delete identifier collisions. Fax numbers remain phone-number records attached
to the profile contract.

.. index:: Backend, CLI, ext:academic_persons
