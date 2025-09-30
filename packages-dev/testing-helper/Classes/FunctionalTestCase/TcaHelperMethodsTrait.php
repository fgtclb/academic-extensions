<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Provides helper methods dealing with TCA in functional tests.
 *
 * * @see TcaHelperMethodsTrait::createTCABackup()
 * * @see TcaHelperMethodsTrait::restoreTCABackup()
 *
 * @todo Extract into dedicated public helper package with TYPO3 core and/or testing-framework constraints.
 */
trait TcaHelperMethodsTrait
{
    private ?array $traitBackupTCA = null;

    /**
     * @param bool $force If true backup is forcefully created, otherwise exception is thrown if backup already exists.
     * @return void
     */
    private function createTCABackup(bool $force): void
    {
        if (!$force && $this->traitBackupTCA !== null) {
            throw new \RuntimeException(
                sprintf(
                    'TCA backup exists already and backup creation not enforced, $force is "%s" for "%s"',
                    ($force ? 'true' : 'false'),
                    __METHOD__,
                ),
                1759190691,
            );
        }
        $this->traitBackupTCA = $GLOBALS['TCA'];
    }

    /**
     * To be used in extended {@see FunctionalTestCase::tearDown()} method before parent call.
     */
    private function restoreTCABackup(bool $throwExceptionWhenBackupDoesNotExists): void
    {
        if ($throwExceptionWhenBackupDoesNotExists && $this->traitBackupTCA === null) {
            throw new \RuntimeException(
                sprintf(
                    '"%s" called with $throwExceptionWhenBackupDoesNotExists = true and backup does not exists.',
                    __METHOD__,
                ),
                1759190705,
            );
        }
        if ($this->traitBackupTCA !== null) {
            $this->updateGlobalTCA($this->traitBackupTCA);
            $this->traitBackupTCA = null;
        }
    }

    private function updateGlobalTCA(array $tca): void
    {
        // Update global TCA
        $GLOBALS['TCA'] = $tca;
        // TYPO3 v13 ships with TcaSchemaFactory holding object based representation of the global tca,
        // which is immutable by design and disallowing dynamic changes to TCA - or to be precise, the
        // changes on global TCA are not auto updating the object structure and because TYPO3 codebase
        // uses that in a wide range of places not working on changed TCA, we need to forcefully reload
        // the structure to update it with test related changes and/or resetting it on tearDown to have
        // a clean state.
        if (class_exists(TcaSchemaFactory::class)) {
            /** @var TcaSchemaFactory $tcaSchemaFactory */
            $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
            $tcaSchemaFactory->load($GLOBALS['TCA'], true);
        }
    }
}
