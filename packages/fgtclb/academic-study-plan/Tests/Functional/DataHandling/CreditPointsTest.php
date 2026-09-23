<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\DataHandling;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The credit points of a semester and of a module take up to two decimals, the way
 * the backend form saves them: through the DataHandler, which formats a decimal
 * number field to two decimals before it writes it.
 *
 * The value is compared as a number: MariaDB, MySQL and PostgreSQL return the decimal
 * column as the string "2.50", SQLite returns the number 2.5.
 */
final class CreditPointsTest extends AbstractAcademicStudyPlanTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CreditPoints/base.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: float}>
     */
    public static function creditPointsDataProvider(): \Generator
    {
        foreach (['semester', 'module'] as $record) {
            $table = 'tx_academicstudyplan_domain_model_' . $record;
            yield $record . ': a half point' => [$table, '2.5', 2.5];
            yield $record . ': a whole number' => [$table, '30', 30.0];
            yield $record . ': a decimal comma' => [$table, '2,5', 2.5];
            // The DataHandler rounds to two decimals, as for every decimal field.
            yield $record . ': more than two decimals' => [$table, '2.555', 2.56];
        }
    }

    #[Test]
    #[DataProvider('creditPointsDataProvider')]
    public function creditPointsAreStoredWithTwoDecimals(string $table, string $entered, float $stored): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                $table => [
                    'NEW1' => [
                        'pid' => 1,
                        'label' => 'Written by the data handler',
                        'credit_points' => $entered,
                    ],
                ],
            ],
            [],
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $uid = (int)$dataHandler->substNEWwithIDs['NEW1'];
        $row = $this->getConnectionPool()
            ->getConnectionForTable($table)
            ->select(['credit_points'], $table, ['uid' => $uid])
            ->fetchAssociative();
        $this->assertIsArray($row);
        $this->assertIsNumeric($row['credit_points']);
        $this->assertSame($stored, (float)$row['credit_points']);
    }
}
