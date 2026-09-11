<?php

declare(strict_types=1);

/**
 * Import map of the one frontend module of the seed package, the demonstration
 * of the frontend icon API on the icon overview page (ACE-595). The module
 * imports the icon factory of academic_base by its bare specifier. The import
 * map of a page only carries the prefixes of the packages the requested module
 * declares here, so "academic_base" has to be named, or the specifier does not
 * resolve in the browser.
 *
 * Like the icon overview itself, this relies on the academic extensions being
 * installed next to the seed package, which the development instances are for;
 * composer.json does not require them.
 */
return [
    'dependencies' => [
        'core',
        'academic_base',
    ],
    'imports' => [
        '@fgtclb/academics-dev-site/frontend/' => 'EXT:academics_dev_site/Resources/Public/JavaScript/frontend/',
    ],
];
