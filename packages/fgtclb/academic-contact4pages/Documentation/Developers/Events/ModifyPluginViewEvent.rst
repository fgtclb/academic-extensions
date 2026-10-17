..  _developers-event-modify-plugin-view-event:

=====================
ModifyPluginViewEvent
=====================

:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` belongs to
:guilabel:`academic_base`, and the plugins of seven academic extensions
dispatch it. It hands a listener the view of a plugin once the plugin has
assigned its own variables, so a listener adds a variable to the templates
without replacing the controller. The event itself, and the rules that apply to
every plugin, are described on :ref:`its page in the manual of academic_base
<fgtclb/academic-base:developers-event-modify-plugin-view-event>`.
This page describes what the content element of
:guilabel:`academic_contacts4pages` passes in.

When it is dispatched
=====================

:php:`ContactsController::listAction()` dispatches it once each time the
content element :ref:`Person’s Contacts on the Page <integrator-contacts-list>`
renders, as the last step of the action, after
:ref:`ModifyPageContactsEvent <developers-event-modify-page-contacts-event>` and
after the view variables are assigned. The data processor for page templates
renders no plugin and does not dispatch it.

What this extension passes in
=============================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the content element: the extension name
            :php:`AcademicContacts4pages`, the plugin name :php:`List`, the
            action name :php:`list`, the request, the site and its language,
            the content object, and the settings of the content element, its
            FlexForm merged over
            :typoscript:`plugin.tx_academiccontacts4pages.settings`.
    *   -   :php:`getView()`
        -   The view, with :html:`{contacts}`, :html:`{roles}`,
            :html:`{contactsWithoutRole}`, :html:`{data}` and :html:`{record}`
            assigned.

Example
=======

A site shows a link to its contact form below the contacts of every page. The
uid of the page with the form is a site setting of the site package, and a
listener hands it to the template of the content element:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddContactFormPageToThePageContacts.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddContactFormPageToThePageContacts
    {
        #[AsEventListener(identifier: 'my-extension/add-contact-form-page-to-the-page-contacts')]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context->getControllerExtensionName() !== 'AcademicContacts4pages'
                || $context->getPluginName() !== 'List'
            ) {
                return;
            }
            $pageUid = (int)($context->getSite()?->getSettings()->get('myExtension.contactFormPage') ?? 0);
            if ($pageUid > 0) {
                $event->getView()->assign('contactFormPage', $pageUid);
            }
        }
    }

An override of :file:`Templates/Contacts/List.html` renders the link with
:html:`<f:link.page pageUid="{contactFormPage}">`.

Rules and pitfalls
==================

**Check the extension name as well as the plugin name.** :php:`List` is the
plugin name of plugins of other academic extensions too.

**A replaced variable is replaced in the view only.** Which contacts a page
shows belongs in
:ref:`ModifyPageContactsEvent <developers-event-modify-page-contacts-event>`,
which reaches the data processor as well, and which keeps the roles and the
contacts without role in line with the contacts.

**The content element is cached.** It is rendered when the page is rendered,
and the page is cached with what the listener assigned, so a listener cannot
assign something different for different visitors.

Introduced in
=============

*   :ref:`feature-plugin-view-event` made the content element dispatch the
    event.
