<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Tca;

use FGTCLB\AcademicBase\Tca\Exception\TableDoesNotExistInTcaException;
use FGTCLB\AcademicBase\Tca\TableConfiguration;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\TcaHelperMethodsTrait;
use PHPUnit\Framework\Attributes\Test;

final class TableConfigurationTest extends AbstractAcademicBaseTestCase
{
    use TcaHelperMethodsTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTCABackup(false);
    }

    protected function tearDown(): void
    {
        $this->restoreTCABackup(true);
        parent::tearDown();
    }

    #[Test]
    public function notExistingTableThrowsRuntimeException(): void
    {
        $this->expectException(TableDoesNotExistInTcaException::class);
        $this->expectExceptionCode(1759201469);
        TableConfiguration::create('not_existing_table');
    }

    #[Test]
    public function rawConfigurationMatchesTcaConfiguration(): void
    {
        $GLOBALS['TCA']['faked_table'] = [];
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('faked_table', $tableConfiguration->tableName);
        $this->assertSame([], $tableConfiguration->rawConfiguration);
        $this->assertNull($tableConfiguration->createdAtFieldName);
        $this->assertNull($tableConfiguration->updatedAtFieldName);
        $this->assertNull($tableConfiguration->startTimeFieldName);
        $this->assertNull($tableConfiguration->endTimeFieldName);
        $this->assertNull($tableConfiguration->deletedFieldName);
        $this->assertNull($tableConfiguration->disabledFieldName);
        $this->assertNull($tableConfiguration->languageFieldName);
        $this->assertNull($tableConfiguration->translationSourceFieldName);
        $this->assertNull($tableConfiguration->transOrigPointerFieldName);
        $this->assertNull($tableConfiguration->transOrigDiffSourceFieldName);
        $this->assertNull($tableConfiguration->origUidFieldName);
    }

    #[Test]
    public function createdAtFieldNameCanBeRetrieved(): void
    {
        $tableTcaConfiguration = [
            'ctrl' => [
                'crdate' => 'created_at',
            ],
        ];
        $GLOBALS['TCA']['faked_table'] = $tableTcaConfiguration;
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('created_at', $tableConfiguration->createdAtFieldName);
    }

    #[Test]
    public function updatedAtFieldNameCanBeRetrieved(): void
    {
        $tableTcaConfiguration = [
            'ctrl' => [
                'tstamp' => 'updated_at',
            ],
        ];
        $GLOBALS['TCA']['faked_table'] = $tableTcaConfiguration;
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('updated_at', $tableConfiguration->updatedAtFieldName);
    }

    #[Test]
    public function startTimeFieldNameCanBeRetrieved(): void
    {
        $tableTcaConfiguration = [
            'ctrl' => [
                'enablecolumns' => [
                    'starttime' => 'start_time',
                ],
            ],
        ];
        $GLOBALS['TCA']['faked_table'] = $tableTcaConfiguration;
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('start_time', $tableConfiguration->startTimeFieldName);
    }

    #[Test]
    public function endTimeFieldNameCanBeRetrieved(): void
    {
        $tableTcaConfiguration = [
            'ctrl' => [
                'enablecolumns' => [
                    'endtime' => 'end_time',
                ],
            ],
        ];
        $GLOBALS['TCA']['faked_table'] = $tableTcaConfiguration;
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('end_time', $tableConfiguration->endTimeFieldName);
    }

    #[Test]
    public function disabledFieldNameCanBeRetrieved(): void
    {
        $tableTcaConfiguration = [
            'ctrl' => [
                'enablecolumns' => [
                    'disabled' => 'hidden',
                ],
            ],
        ];
        $GLOBALS['TCA']['faked_table'] = $tableTcaConfiguration;
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('hidden', $tableConfiguration->disabledFieldName);
    }

    #[Test]
    public function deletedFieldNameCanBeRetrieved(): void
    {
        $tableTcaConfiguration = [
            'ctrl' => [
                'delete' => 'deleted',
            ],
        ];
        $GLOBALS['TCA']['faked_table'] = $tableTcaConfiguration;
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('deleted', $tableConfiguration->deletedFieldName);
    }

    #[Test]
    public function languageFieldNameCanBeRetrieved(): void
    {
        $tableTcaConfiguration = [
            'ctrl' => [
                'languageField' => 'sys_language_uid',
            ],
        ];
        $GLOBALS['TCA']['faked_table'] = $tableTcaConfiguration;
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('sys_language_uid', $tableConfiguration->languageFieldName);
    }

    #[Test]
    public function translationSourceFieldNameCanBeRetrieved(): void
    {
        $tableTcaConfiguration = [
            'ctrl' => [
                'translationSource' => 'l10n_source',
            ],
        ];
        $GLOBALS['TCA']['faked_table'] = $tableTcaConfiguration;
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('l10n_source', $tableConfiguration->translationSourceFieldName);
    }

    #[Test]
    public function transOrigPointerFieldNameCanBeRetrieved(): void
    {
        $tableTcaConfiguration = [
            'ctrl' => [
                'transOrigPointerField' => 'l10n_parent',
            ],
        ];
        $GLOBALS['TCA']['faked_table'] = $tableTcaConfiguration;
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('l10n_parent', $tableConfiguration->transOrigPointerFieldName);
    }

    #[Test]
    public function transOrigDiffSourceFieldNameCanBeRetrieved(): void
    {
        $tableTcaConfiguration = [
            'ctrl' => [
                'transOrigDiffSourceField' => 'l10n_diffsource',
            ],
        ];
        $GLOBALS['TCA']['faked_table'] = $tableTcaConfiguration;
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('l10n_diffsource', $tableConfiguration->transOrigDiffSourceFieldName);
    }

    #[Test]
    public function origUidFieldNameCanBeRetrieved(): void
    {
        $tableTcaConfiguration = [
            'ctrl' => [
                'origUid' => 't3orig_uid',
            ],
        ];
        $GLOBALS['TCA']['faked_table'] = $tableTcaConfiguration;
        $this->updateGlobalTCA($GLOBALS['TCA']);
        $tableConfiguration = TableConfiguration::create('faked_table');
        $this->assertSame('t3orig_uid', $tableConfiguration->origUidFieldName);
    }
}
