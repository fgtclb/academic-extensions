.. _important-contacts-without-visible-profile-are-skipped:

=========================================================
Important: Contacts without a visible profile are skipped
=========================================================

Description
===========

A contact of a page points at a contract of :guilabel:`EXT:academic_persons`,
and that contract points at a profile. Both are relations, and Extbase resolves
a relation with fresh query settings: a hidden contract, or a profile that is
hidden, outside its start and end time or restricted to a frontend user group
the visitor does not belong to, resolved to nothing instead of raising an error.
The contact record itself was still visible, so the contacts of a page rendered
an empty card below its role heading - a person who had left the institution
kept a slot on the page.

The contacts content element and the data processor
:php:`\FGTCLB\AcademicContacts4pages\DataProcessing\ContactsProcessor` now leave
such a contact out. Both ask the same service for the contacts of a page, so
they cannot drift apart, and a page template of a project no longer needs a
guard of its own.

Impact
======

- A contact whose contract or profile is not visible to the visitor is not
  rendered, in the content element and in the page data processor alike.
- A role heading is rendered only while at least one shown contact carries that
  role. The role of a single hidden person disappears with that person, instead
  of heading an empty group.
- The plugin option :guilabel:`Show hidden records` is unchanged in meaning: it
  shows hidden *contact records*, and never a hidden contract or profile. A
  hidden contact pointing at a visible person is still shown with the option
  enabled; a visible contact pointing at a hidden person is not.
- The view variable :html:`{contacts}` and the processed key ``contacts`` are
  now a plain list instead of a query result. Fluid is unaffected -
  :html:`<f:for>` and :html:`<f:count>` behave identically - but PHP reading
  either as a query result has to accept a list.
- :php:`ContactsProcessor` is published as a service and takes its collaborator
  through the constructor. A project that *subclasses* the processor and names
  the subclass in TypoScript has to let its own :file:`Services.yaml` autowire
  that subclass; otherwise TYPO3 instantiates it without arguments.

Nothing has to be configured, and no data is migrated. Making a person visible
again restores every contact pointing at it.

Affected Installations
======================

Every installation rendering page contacts whose database holds a hidden
contract, or a profile that is hidden, timed or restricted to a frontend user
group. Installations that carry a patch or a template guard against empty
contact cards can drop it.

.. index:: Frontend, Fluid, ext:academic_contacts4pages
