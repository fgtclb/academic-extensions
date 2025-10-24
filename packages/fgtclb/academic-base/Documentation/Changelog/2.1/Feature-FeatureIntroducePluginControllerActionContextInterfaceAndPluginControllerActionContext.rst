.. include:: /Includes.rst.txt

.. _feature-1758794400:

===============================================================================================
Feature: Introduce `PluginControllerActionContextInterface` and `PluginControllerActionContext`
===============================================================================================

Description
===========

Interface `\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
is introduced to describe a shared DTO container for generic data structures required in
extbase controller action based events. The interface **should** be used for native type
declaration in the event, and either a custom implementation based on the event or default
implementation `\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext` can
be used to create container when dispatching the events. In most cases provided default
implementation should be enough, and additional data provided directly as event property
and getter/setter.

Example Usage
=============

Custom event
------------

..  code-block:: php
    :caption: EXT:my_ext/Classes/Event/MyCustomControllerActionEvent.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExt\Event;

    use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;

    final class MyCustomControllerActionEvent
    {
        public function __construct(
            private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
            // ... additional properties
        ) {}

        public function getPluginControllerActionContext(): PluginControllerActionContextInterface
        {
            return $this->pluginControllerActionContext;
        }
    }

Dispatch custom event in a controller providing the context information
-----------------------------------------------------------------------

..  code-block:: php
    :caption: EXT:my_ext/Classes/Controller/MyController.php

    <?php

     declare(strict_types=1);

     namespace MyVendor\MyExt\Controller;

     use Psr\EventDispatcher\EventDispatcherInterface;
     use Symfony\Contracts\HttpClient\ResponseInterface;
     use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
     use MyVendor\MyExt\Event\MyCustomControllerActionEvent;

     final class MyController extends ActionController
     {
         public function __construct(
             private readonly EventDispatcherInterface $eventDispatcher,
         ) {}

         public function customAction(): ResponseInterface
         {
             // Dispatch custom PSR-14 event having at least the plugin controller
             // action context data attached, allowing to determine the action and
             // plugin data within an event listener avoiding that this has to be
             // loaded manually again.
             $event = $this->eventDispatcher->dispatch(new MyCustomControllerActionEvent(
                 pluginControllerActionContext: new PluginControllerActionContext(
                     request: $this->request,
                     settings: $this->settings,
                 ),
             ));
         }
     }

.. index:: Frontend, EXT:extbase
