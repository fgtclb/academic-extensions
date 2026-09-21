<?php

// Keep the "Package.providesPackages" key and the version in composer.json next
// to this file. From TYPO3 v14 on, PackageManager::isComposerOnlyCapable()
// skips merging this file into the composer manifest only when both are
// declared; without them v14 merges the constraints below over the composer
// "require" and the upgrade check tests fail on v14 alone.
$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Upgrade check project',
    'description' => 'A project site package overriding templates of the upgrade check fixture extension',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'academic_base' => '3.0.0',
        ],
    ],
];
