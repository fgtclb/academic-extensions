..  _feature-profile-editing-contract-contact-management:

===================================================
Feature: ProfileEditing Contract contact management
===================================================

Description
===========

Employee Contracts in the Vue profile editor are writable again and
expose their complete configured field set. A Contract read view contains the
configured physical-address, email-address and phone-number sections. Each
section supports creating, viewing, editing and deleting its records without a
page reload. Add forms open below their contact-section heading; view, edit and
delete forms open directly below the selected contact record.

Contract and contact edit fields expose localized helptext popovers. The
physical-address country field is a localized select backed by TYPO3's
``CountryProvider`` and persists validated ISO alpha-2 codes.

Every contact section has its own persistent order. Up/down controls use the
same normalized sorting behavior as other structured profile records, with
the unavailable direction disabled on the first and last record. New records
are appended to their section.

The JSON endpoints resolve the Contract through the authenticated Profile and
resolve a contact only within that Contract and section. A foreign contact UID
is rejected, and read-only Contract configuration blocks all contact mutations.

Impact
======

ProfileEditing template overrides must retain the
``data-*-contract-contact-url`` attributes and the
``data-ie-contract-contact-*`` hooks. Contact fields and validation continue to
come from the shared ``contracts.contactSections.<section>.fields``
configuration. The
``documentSections.contracts`` entry only references ``type: contracts``.

..  index:: AJAX, Contracts, Email, Frontend, NotScanned, Phone, ext:academic_persons_edit
