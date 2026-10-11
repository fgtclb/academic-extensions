.. _important-ace-322-academic-contacts4pages:

=================================================
Important: Contacts without a role are listed too
=================================================

Description
===========

The :guilabel:`Contacts for this page` content element lists the contacts of a
page below a heading per role. Version 2.3 rendered only the contacts that
belong to one of these roles. As soon as one contact of the page had a role,
every contact without a role was left out of the output. Only a page whose
contacts had no role at all listed them, in one list without headings.

The contacts without a role are now rendered after the role groups, in a row
of their own without a heading. The content element passes them to its
template as the new variable :html:`{contactsWithoutRole}`.

Impact
======

A page that mixes contacts with and without a role shows all of its contacts.
Pages whose contacts all have a role, and pages whose contacts have none, render
as before. TYPO3 v12 and v13 behave alike.

An installation that overrides :file:`Templates/Contacts/List.html` keeps its
own output and still leaves those contacts out. Render
:html:`{contactsWithoutRole}` after the role groups to show them:

..  code-block:: html

    <f:if condition="{contactsWithoutRole}">
        <div class="row">
            <f:for each="{contactsWithoutRole}" as="contact">
                <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                    <f:render partial="Contacts/Item" arguments="{
                        contact: contact,
                        profile: contact.contract.profile,
                        contract: contact.contract,
                        settings: settings,
                        data: data
                    }" />
                </div>
            </f:for>
        </div>
    </f:if>

Affected Installations
======================

Installations with page contacts of which some have a role and some do not.

.. index:: Frontend, Fluid, ext:academic_contacts4pages
