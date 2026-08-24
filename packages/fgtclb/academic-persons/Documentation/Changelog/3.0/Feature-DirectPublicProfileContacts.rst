..  _feature-direct-public-profile-contacts:

=======================================
Feature: Direct public Profile contacts
=======================================

Description
===========

``Profile`` now stores ``emailAddress`` and ``phoneNumber`` independently from
the email and telephone records of its Contracts. The boolean
``publishEmailAddress`` and ``publishPhoneNumber`` properties are explicit
opt-in controls and default to false.

The public Profile detail renders the direct values only when the matching
publication flag and a non-empty value are present. Contract contacts remain
in the contract accordion and continue to use their own records, visibility
state and validation.

The four fields are declared in the :yaml:`profile` settings map. Contract
contact fields live in the separate :yaml:`contractContact` map.

Impact
======

Run the TYPO3 database schema update to create :sql:`email_address`,
:sql:`publish_email_address`, :sql:`phone_number` and
:sql:`publish_phone_number` on the Profile table. Existing profiles publish no
new contact data until an editor supplies a value and explicitly enables its
flag.

..  index:: Database, Email, Frontend, Privacy, Profile, Telephone
