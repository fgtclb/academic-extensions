# Page contacts

`academic_contacts4pages` shows the contacts of a page in two places: the
contacts content element, and a page template through the data processor
`academic-page-contacts`. Both used to read the contacts on their own, and they
drifted apart. The content element got the list of contacts without a role
(ACE-322) and the page template did not. The content element honoured "show
hidden records" and the processor had no options at all. A project that wanted
to change which contacts a page shows had to copy the controller, and its
change then reached the content element only.

This page is about how the two are kept together. What an integrator sets and
what a listener receives is documented in the `Documentation/` of the
extension, under *Configuration* and *For developers*.

## One provider, one event

Both outputs ask `PageContactsProvider` and render the `PageContacts` value
object it returns: `contacts`, `roles` and `contactsWithoutRole`. The provider
does four things, in this order:

1. It reads the contacts of the page through `ContactRepository::findByPid()`,
   with hidden contacts when the caller asks for them.
2. It drops the contacts whose contract or profile does not resolve for the
   current visitor (ACE-101).
3. It dispatches `ModifyPageContactsEvent` with the remaining list.
4. It copies each contact of the list the listeners handed back, hands the copy
   the address record provider while hidden records are shown, and groups the
   copies into roles and contacts without role.

Dispatching inside the provider is the point. One event per output would let a
listener register for one and forget the other, which is how the outputs
drifted in the first place. Grouping after the event means a listener that
removes the last contact of a role removes the role as well, without having to
know that roles exist.

The event tells a listener which output asked through `getOutput()`, a
`PageContactsOutput` case. It also carries the plugin action context of
`academic_base`, which [Class design](class-design.md#extension-points)
requires of every event a plugin action dispatches, and that context is `null`
when the data processor asked, because there is no plugin. The two are separate
on purpose: the case says which output, the context says which content element.

## What a listener cannot rely on

- **A contact a listener adds is rendered as it is.** The provider does not
  check it again, exactly as a replaced result of the
  [list plugin events](list-plugin-events.md) is rendered as it is.
- **The page is cached with the result.** Both outputs run while the page is
  rendered, so a listener cannot vary the list per visitor of a cached page.
- **The objects are shared, the list is not.** Extbase hands every output of a
  request the same contact, contract, profile and role objects. A listener that
  changes a property of one changes it for all outputs, so it changes the list
  and leaves the objects alone.

## Why the provider copies the contacts

Whether a contact shows its hidden address records is state on the contact
object, and Fluid reads the address records only when it prints them. The data
processor runs before the page template renders, and a content element that the
page template renders asks the provider while the template renders, before the
template prints the contacts of the page further down. With the option set on
the shared objects, the last output that asked would decide for all of them.
Resetting it after each call would not help either, since the page output
prints its contacts after the content element has asked. Each call therefore
hands out shallow copies of the contacts: the option lives on the copy, and the
contract, profile and role behind it stay shared. The two tests of
`ModifyPageContactsEventTest` that render the content element inside the page
template fail without the copy.

## The processor registration

The processor is tagged `data.processor` with the identifier
`academic-page-contacts` in `Configuration/Services.yaml`. TYPO3 has no
attribute of its own for data processors on v13 or v14, and Symfony's
`#[AutoconfigureTag]` is registered as an `_instanceof` rule that a project's
subclass of the processor would inherit, identifier included. The shipped
TypoScript uses the identifier, and installations that name the class keep
working because the service stays public through the
`#[Autoconfigure(public: true)]` it had before. The class stays open to
subclasses, see [Dependency injection](dependency-injection.md#a-data-processor-with-a-collaborator-has-to-be-published).

`ContactsProcessorTest` renders a page through each of the two names, and
through each option. `ModifyPageContactsEventTest` renders one page with both
outputs while the fixture listener of `test_page_contacts_listener` removes a
contact from both, from the page output only, or from one of two content
elements through its FlexForm, and renders the content element after and
inside the page template with hidden records shown by one output only.

## See also

- [List plugin events](list-plugin-events.md): the events of the list plugins,
  whose rule about a replaced result this event follows.
- [Plugin view event](plugin-view-event.md): the view event the contacts
  content element dispatches after this one.
- [Class design](class-design.md): how an event is shaped and what is public
  API.
- [Dependency injection](dependency-injection.md): why the processor is a
  public service and where its tag is set.
