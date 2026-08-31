..  _introduction:

What does it do?
================

This TYPO3 extension assigns people to pages and displays them in the frontend,
for example the dean's office of a faculty page, the participants of a research
page or the contact persons of a study programme.

A person is not maintained here: the extension builds on `EXT:academic_persons`
and points at one of the contracts of a profile. Everything that is displayed -
the name, the position, the location, the email addresses, the phone numbers and
the physical addresses - comes from that contract, so a change to the person has
to be made once and reaches every page the person is a contact of.

..  _introduction-contact-records:

Contact records
---------------

A contact is a record of its own, maintained either in the :guilabel:`Contacts`
tab of a page or in the :guilabel:`Linked pages` tab of a contract. Both edit
the same record, so a person can be added from the page it belongs on as well
as from the person itself.

A contact record consists of:

:guilabel:`Page`
    The page the person is a contact of. Filled automatically when the record
    is created from a page.

:guilabel:`Contract`
    The contract of the person to display. Filled automatically when the record
    is created from a contract.

:guilabel:`Role`
    An optional role, for example :guilabel:`Dean's office` or
    :guilabel:`Student advisors`. Contacts sharing a role are rendered as a
    group below the name of that role, contacts without a role are rendered
    below the grouped ones. Roles are records of their own and are usually kept
    in a storage folder.

..  _introduction-frontend:

Frontend output
---------------

The contacts of a page are rendered either with the content element
:guilabel:`Contacts for this page`, which can be placed anywhere on the page,
or directly in a page template through the data processor
:php:`FGTCLB\AcademicContacts4pages\DataProcessing\ContactsProcessor`, which
adds the contacts and their roles to the page rendering. Both display the person
through the :file:`Profile/Item` partial of `EXT:academic_persons`, so contacts
look like the profiles rendered by that extension.

..  _introduction-page-translations:

Contacts and page translations
------------------------------

The :guilabel:`Page` field of a contact always stores the uid of the
default-language page — that is how TYPO3 models references to pages, and it
does not change when the contact is translated.

Since version 3.0 a contact follows its page's translations: localizing a
contact — directly, by translating the contract or profile above it, or through
the translation synchronisation of `EXT:academic_persons` — only yields a
translated contact when the page it points at is translated into that language.
For an untranslated page the freshly localized contact is removed again
immediately (in the live workspace as a regular soft delete, in a workspace the
new record is discarded), because such a translation carries no content of its
own and would only make the contact appear twice on the page.

The frontend shows each contact **exactly once per language**: where a
translation exists it represents the contact, otherwise the default-language
record is shown. Translated contact duplicates created by versions before 3.0
therefore stop rendering without any database cleanup; the rows themselves are
left untouched. Contacts copied into a language without a connection to a
default-language record (free mode) are independent records and are exempt from
all of this.
