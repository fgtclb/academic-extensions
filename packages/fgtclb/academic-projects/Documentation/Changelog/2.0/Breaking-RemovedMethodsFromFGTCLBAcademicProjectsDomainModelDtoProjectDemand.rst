..  include:: /Includes.rst.txt

..  _breaking-1771363889:

========================================================================================
Breaking: Removed methods from `\FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand`
========================================================================================

Description
===========

Following class methods has been removed:

* `ProfileDemand::getHideCompletedProjects()`
* `ProfileDemand::setHideCompletedProjects()`

Impact
======

Using above mentioned removed :php:`ProfileDemand` methods to call related
methods in the :php:`ProfileRepository` directly leads to `FATAL PHP ERROR`.

Affected installations
======================

TYPO3 instances using removed :php:`ProfileDemand` methods.

Migration
=========

The removed method should be replaced using the new methods:

* `ProfileDemand::setActiveState(string $activeState): void`
* `ProfileDemand::getActiveState(): string`

instead, working slightly different now.

:php:`\FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState` ENUM provided
possible values for the `active state` but cannot be passed directly.

..  code-block::
    :caption: before

    $demand = new ProfileDemand();
    $demand->setHideCompletedProjects(true);
    $demand->setHideCompletedProjects(false);

..  code-block::
    :caption:: after

    $demand = new ProfileDemand();
    // setHideCompletedProjects(true)
    $demand->setActiveState(ActiveState::ACTIVE);
    // setHideCompletedProjects(false)
    $demand->setActiveState(ActiveState::ALL);

..  tip::

    The new :php:`ActiveState` enum provides the additional
    `ActiveStatus::COMPLETED` state, which can be used to
    filter only completed projects.

..  index:: Backend, FullyScanned, ext:about
