..  _important-1790607225:

=================================================================
Important: The events of one plugin rendering share their context
=================================================================

Description
===========

An academic plugin that dispatched several events while it rendered handed each
of them a plugin action context of its own. The plugin view event built one from
the request and the settings, and the partner and project lists built another
one for their demand and list events, as the persons plugins did for their query
and page title events.

Every action now builds its context once, before its first event and after its
settings are settled, and hands the same object to every event it dispatches:

*   :php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent`,
*   the demand and list events of :guilabel:`academic_partners`,
    :guilabel:`academic_programs` and :guilabel:`academic_projects`,
*   the query and page title events of :guilabel:`academic_persons`,
*   and the write event of :guilabel:`academic_persons_edit`.

Impact
======

A listener of several events of one rendering receives one context, with the
settings the action uses, and can keep what it collects for one rendering
keyed by that object.

The persons plugins hand the view event the persons context they already
handed their query events. It implements
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`,
which the event declares, so a listener typed against that interface notices
nothing. A listener that checked for the class
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext`
checks for the interface instead.

The internal helper the controllers dispatch the view event with takes the
context instead of the request and the settings. It is marked :php:`@internal`
and is not meant to be called by a project.

..  index:: Frontend, PHP-API, ext:academic_base
