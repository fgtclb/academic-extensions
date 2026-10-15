..  _developers-event-collect-frontend-icons-event:

=========================
CollectFrontendIconsEvent
=========================

:php:`\FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent` lets code
contribute icons to the :ref:`frontend icon registry
<configuration-frontend-icons>` of this extension. It is meant for icons whose
identifiers are only known at runtime, read from a configuration file of an
extension for example. An icon a package ships with a fixed identifier belongs
in its :file:`Configuration/FrontendIcons.php` instead.

:guilabel:`category_types` uses the event for the icons of the category types
and category groups an installation registers.

..  contents::
    :local:
    :depth: 1

..  _developers-event-collect-frontend-icons-event-dispatch:

When it is dispatched
=====================

The registry dispatches the event each time it is built, before it reads the
:file:`Configuration/FrontendIcons.php` files of the active packages. The
registry is kept with the system caches, so that happens when the cache entry
is missing, after the system caches were flushed, and when
:bash:`vendor/bin/typo3 cache:warmup` builds it. It does not happen per
request: whichever request or command finds the cache entry missing builds it
for the whole installation. What a listener contributes therefore changes only
after the system caches are flushed again.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Method
        -   What it does
    *   -   :php:`addIcon(string $identifier, string $providerClass, array $options = [])`
        -   Contributes the icon :php:`$identifier`, rendered by the icon
            provider :php:`$providerClass` with :php:`$options`, the options
            of an entry of :file:`Configuration/FrontendIcons.php` without
            :php:`provider`, :php:`source` for example. A second call for the
            same identifier replaces the first one.
    *   -   :php:`getIcons()`
        -   The icons contributed so far, in the format of
            :file:`Configuration/FrontendIcons.php`.

..  _configuration-frontend-icons-code:

Example: icons from code
========================

An extension that computes its icons, from a configuration file of its own for
example, contributes them through the event. It is dispatched while the
registry is built, before the files are read, so an entry of a
:file:`Configuration/FrontendIcons.php` with the same identifier replaces a
contributed icon whatever the order of the two packages.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ContributeFrontendIcons.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

    #[AsEventListener(identifier: 'my-extension/contribute-frontend-icons')]
    final readonly class ContributeFrontendIcons
    {
        public function __invoke(CollectFrontendIconsEvent $event): void
        {
            $event->addIcon(
                'my-extension-badge',
                SvgIconProvider::class,
                ['source' => 'EXT:my_extension/Resources/Public/Icons/badge.svg'],
            );
        }
    }

:php:`addIcon()` rejects a provider that is not an icon provider with an error
that names the icon.

..  _developers-event-collect-frontend-icons-event-rules:

Rules and pitfalls
==================

*   **A file always wins.** The contributed icons come first and the files are
    merged over them, so a site package replaces a contributed icon by
    registering the same identifier in its
    :file:`Configuration/FrontendIcons.php`, whatever the loading order of
    the packages.
*   **Flush after a change.** A changed listener, or a change to what it reads,
    reaches the frontend after :bash:`vendor/bin/typo3 cache:flush --group
    system`, see :ref:`configuration-frontend-icons-cache`.
*   **One registry for every site.** Do not make what a listener contributes
    depend on the request, the site or the language. The registry is built
    once for the whole installation, by a command as often as by a request.
*   **Only real providers.** :php:`addIcon()` throws an
    :php:`\InvalidArgumentException` with code 1791061902 for a class that does
    not implement :php:`\TYPO3\CMS\Core\Imaging\IconProviderInterface`. An
    icon registered for the frontend only is unknown to the icon registry of
    TYPO3, so an icon the backend shows as well is registered there, too.

Introduced in
=============

*   :ref:`feature-1791061865`
*   :ref:`feature-frontend-icon-map`, which serves the contributed icons to
    JavaScript as well
