..  _feature-plugin-view-event:

====================================================
Feature: The plugin dispatches the plugin view event
====================================================

Description
===========

The contacts plugin of :guilabel:`academic_contacts4pages` dispatches
:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` of
:guilabel:`academic_base`, once each time it renders, after assigning its own
variables. A listener adds a variable to its templates without replacing the
controller.

The context of the event names the extension :php:`AcademicContacts4pages` and
the plugin name :php:`List`, which a listener checks for the plugin it means.

The event, with an example listener, is described in the `changelog of
academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/3.0/Feature-ModifyPluginViewEvent.html>`__.

Impact
======

A project adds a variable to the templates of this plugin with an event listener
instead of a subclass or a copy of the controller. The behaviour is
the same on TYPO3 v13 and v14.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_contacts4pages
