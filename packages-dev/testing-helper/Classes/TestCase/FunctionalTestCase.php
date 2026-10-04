<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\TestCase;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase as SiteBasedFunctionalTestCase;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;

/**
 * The base of every functional test case of the repository, directly or through the
 * abstract test case of an extension. `FunctionalTestBaseClassTest` of
 * `packages-dev/monorepo-shared` fails for a functional test class that does not extend it.
 *
 * It adds the configuration every test instance starts from. What a test class puts into
 * `$configurationToUseInTestInstance` before it calls `parent::setUp()` is merged over it
 * and wins, so a class that needs another value for one of these settings sets it there.
 *
 * The Extbase class schema cache is kept in memory. TYPO3 core writes the class schemata
 * from the destructor of the Extbase reflection service. When the garbage collector runs
 * that destructor inside another `serialize()`, the cache entry is written with back
 * references into the outer payload under a valid signature, and the next read fails with
 * `unserialize(): Error at offset` in `AuthenticatedMessageDeserializer`. Which class hits
 * it depends on the classes that ran before it in the same process, so every change of the
 * test set moved it to another class (ACE-725, ACE-817). The defect is reported to TYPO3
 * core, a patch is under review: https://forge.typo3.org/issues/110909
 *
 * A transient backend is never serialized, and a test process gains nothing from a
 * persisted class schema.
 */
abstract class FunctionalTestCase extends SiteBasedFunctionalTestCase
{
    /**
     * @var array<string, mixed>
     */
    private const DEFAULT_INSTANCE_CONFIGURATION = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'extbase' => [
                        'backend' => TransientMemoryBackend::class,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = array_replace_recursive(
            self::DEFAULT_INSTANCE_CONFIGURATION,
            $this->configurationToUseInTestInstance,
        );
        parent::setUp();
    }
}
