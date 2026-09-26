..  _important-plugin-controller-action-context-content-object:

======================================================================
Important: The plugin action context never fails on the content object
======================================================================

Description
===========

:php:`PluginControllerActionContext::getContentObjectRenderer()` returned
whatever the request carried under the attribute ``currentContentObject``. A
value that was not a :php:`ContentObjectRenderer` - set by a middleware or a
test under that name - made the getter fail with a :php:`\TypeError`,
although it is declared to return :php:`null` when there is no content object.

It now returns :php:`null` for such a value, as
:php:`getExtbaseRequestParameters()` already did for the ``extbase``
attribute. A request that carries a content object, or none, gives the same
result as before.

:php:`getApplicationType()` is unchanged and still throws when the request
carries no application type.

Impact
======

An event listener reading the content element from the context of an academic
plugin gets :php:`null` instead of an exception when the request carries
something else under that name. A plugin rendered as a content element is not
affected.

..  index:: PHP-API, ext:academic_base
