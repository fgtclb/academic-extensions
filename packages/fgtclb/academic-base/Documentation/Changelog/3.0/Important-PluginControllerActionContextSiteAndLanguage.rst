..  _important-plugin-controller-action-context-site-and-language:

============================================================================
Important: The plugin action context never fails on the site or the language
============================================================================

Description
===========

:php:`getSite()` and :php:`getLanguage()` of the plugin action context
returned whatever the request carried under the attributes ``site`` and
``language``. A value of another kind made them fail with a :php:`\TypeError`,
although both are declared to return :php:`null` when the value is missing.
This included the :php:`NullSite` the backend stores for page 0 and for a
page outside any site, which implements :php:`SiteInterface` but is no
:php:`Site`.

Both getters now return :php:`null` for such a value, as
:php:`getContentObjectRenderer()` does since
:ref:`important-plugin-controller-action-context-content-object`. A request
that carries a site and a language, or none, gives the same result as before.
This applies to :php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext`
and to the deprecated copy
:php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContext`,
which the page title placeholder event of :guilabel:`academic_persons` hands
over.

:php:`getApplicationType()` is the one getter that still raises. For a
request that carries no application type, TYPO3 throws its
:php:`\RuntimeException` with code 1606222812, and for a type that is neither
frontend nor backend, nor the install tool on TYPO3 v14, its
:php:`\LogicException` with code 1678875015. Every request the frontend or
the backend handled carries a type the getter resolves. The interface
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
now states this.

Impact
======

An event listener reading the site or the language from the context of an
academic plugin gets :php:`null` instead of an exception when the request
carries something else under that name. A plugin rendered on a page of a site
is not affected. The page title of a profile, which reads the language from
the context, then uses the language of the logged-in backend user, or English
without one, as it already did for a request without a language.

..  index:: PHP-API, ext:academic_base
