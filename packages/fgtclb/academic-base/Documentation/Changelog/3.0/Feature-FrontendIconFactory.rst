..  _feature-frontend-icon-factory:

===================================================
Feature: A JavaScript icon factory for the frontend
===================================================

Description
===========

The new JavaScript module `@fgtclb/academic-base/frontend/icons.js`, published
in the import map of this extension, gives frontend JavaScript an icon by its
identifier, modelled on the backend icon API of TYPO3. It reads the
:ref:`JSON icon maps <feature-frontend-icon-map>` of the page first and asks
the :ref:`icon endpoint <feature-frontend-icon-endpoint>` for the rest:

..  code-block:: html
    :caption: The template of the plugin

    <div class="my-plugin" data-my-plugin>
        <ab:frontendIconMap identifiers="{0: 'tx-academicbase-action-add'}" endpoint="1" />
    </div>
    <f:asset.module identifier="@my-vendor/my-sitepackage/frontend/my-plugin.js" />

..  code-block:: javascript
    :caption: The module of the plugin

    import { IconFactory, Sizes, endpointFrom } from '@fgtclb/academic-base/frontend/icons.js';

    const root = document.querySelector('[data-my-plugin]');
    const icons = new IconFactory(endpointFrom(root));

    // From the JSON map of the page, without a request.
    button.append(await icons.getIconElement('tx-academicbase-action-add'));
    // From the endpoint, in one request for both.
    const [edit, remove] = await Promise.all([
        icons.getIcon('tx-academicbase-action-edit', Sizes.medium),
        icons.getIcon('tx-academicbase-action-delete', Sizes.medium),
    ]);

:js:`endpointFrom()` reads the endpoint `endpoint="1"` names. An
:js:`IconFactory` answers :js:`getIcon()` with the markup,
:js:`getIconElement()` with a new element on every call and :js:`prefetch()`
once several icons are settled, loaded or not. The icons asked for at the same
time become one
request per endpoint and size, sorted by identifier and split at 32, so the
same icons always make the same URL and a browser or a proxy caches their
answer once. Each icon is asked for once per page and shared by every factory
on it, without cookies. An identifier that is not served rejects the promise.
A malformed one is rejected without a request, so it cannot fail the icons
asked for with it. A request without an answer after ten seconds fails, and
the next call asks again.

The package that ships the module names `academic_base` in the
`dependencies` of its :file:`Configuration/JavaScriptModules.php`, because a
page only carries the import map entries of the packages its modules declare:

..  code-block:: php
    :caption: EXT:my_sitepackage/Configuration/JavaScriptModules.php

    return [
        'dependencies' => ['core', 'academic_base'],
        'imports' => [
            '@my-vendor/my-sitepackage/frontend/' => 'EXT:my_sitepackage/Resources/Public/JavaScript/frontend/',
        ],
    ];

The module and its exports are public API of this extension, see
:ref:`developers-extension-points-api`.

Impact
======

Nothing changes for existing scripts. See
:ref:`The icon factory <icons-frontend-factory>`.

..  index:: Frontend, JavaScript, ext:academic_base
