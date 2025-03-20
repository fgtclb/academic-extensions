<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Boilerplate for a functional test phpunit boostrap file.
 *
 * This file is loosely maintained within TYPO3 testing-framework, extensions
 * are encouraged to not use it directly, but to copy it to an own place,
 * usually in parallel to a FunctionalTests.xml file.
 *
 * This file is defined in FunctionalTests.xml and called by phpunit
 * before instantiating the test suites.
 */
(static function () {
    /**
     * Automatically add fixture extensions to the `typo3/testing-framework`
     * {@see \TYPO3\TestingFramework\Composer\ComposerPackageManager} to
     * allow composer package name or extension keys of fixture extension in
     * {@see \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase::$testExtensionToLoad}.
     */
    if (class_exists(\SBUERK\AvailableFixturePackages::class)) {
        $adopter = (new \SBUERK\AvailableFixturePackages());
        /**
         * Property {@see \SBUERK\AvailableFixturePackages::$dataFile} contains an invalid path,
         * missing one slash to separate vendor name from the data file and failing to read the
         * adopted namespaces. As a quick workaround we use nativ PHP reflection api to set the
         * private property on the final class directly to the correct path for this setup.
         */
        $reflection = new \ReflectionClass($adopter);
        $propertyReflection = $reflection->getProperty('dataFile');
        if (PHP_VERSION_ID < 801000) {
            $propertyReflection->setAccessible(true);
        }
        $propertyReflection->setValue(
            $adopter,
            __DIR__ . '/../../.Build/vendor/sbuerk/fixture-packages.php'
        );
        // ^^ END-OF-WORKAROUND
        $adopter->adoptFixtureExtensions();
    }

    $testbase = new \TYPO3\TestingFramework\Core\Testbase();
    $testbase->defineOriginalRootPath();
    //var_dump(getenv('TYPO3_PATH_ROOT'));var_dump(getenv('TYPO3_PATH_WEB'));var_dump(ORIGINAL_ROOT);die();
    $testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/tests');
    $testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/transient');
})();
