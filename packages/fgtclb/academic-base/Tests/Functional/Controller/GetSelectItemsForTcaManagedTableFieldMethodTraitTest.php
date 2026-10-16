<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Controller;

use FGTCLB\AcademicBase\Controller\GetSelectItemsForTcaManagedTableFieldMethodTrait;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\AcademicBase\Tests\Functional\Controller\Fixtures\RecordingItemsProcFunc;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * The parameters the trait hands to the `itemsProcFunc` handler of a field outside
 * FormEngine, where no record exists.
 *
 * They follow the keys FormEngine passes in every context, in the order of TYPO3 v13,
 * which v14 changed by moving `TSconfig` to the end. `row` is an
 * empty array: the handlers of the academic extensions pass the parameters on to
 * `ModifyTcaSelectFieldItemsEvent`, and a listener that reads `row` without a fallback
 * raised a warning in the job form while the key was missing (ACE-922). The keys of
 * FormEngine that describe an inline parent or the record of a FlexForm are left out,
 * because nothing outside FormEngine has them.
 */
final class GetSelectItemsForTcaManagedTableFieldMethodTraitTest extends AbstractAcademicBaseTestCase
{
    private const TABLE = 'tx_academicbase_test_table';
    private const FIELD = 'test_field';

    /**
     * @var array<string, mixed>
     */
    private array $backupTca = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        RecordingItemsProcFunc::$parameters = null;
        $this->backupTca = $GLOBALS['TCA'];
        $GLOBALS['TCA'][self::TABLE]['columns'][self::FIELD]['config'] = [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [],
            'itemsProcFunc' => RecordingItemsProcFunc::class . '->itemsProcFunc',
        ];
    }

    protected function tearDown(): void
    {
        $GLOBALS['TCA'] = $this->backupTca;
        RecordingItemsProcFunc::$parameters = null;
        parent::tearDown();
    }

    #[Test]
    public function handlerReceivesTheKeysOfFormEngineWithAnEmptyRow(): void
    {
        $site = new Site('acme', 1, []);
        $subject = new class () {
            use GetSelectItemsForTcaManagedTableFieldMethodTrait;

            /**
             * @return array<int<0, max>, array{label: string, value: string}>
             */
            public function items(ServerRequest $request, LocalizationUtility $localizationUtility, string $tableName, string $fieldName): array
            {
                return $this->getSelectItemsForTcaManagedTableField($request, $localizationUtility, 'academic_base', $tableName, $fieldName);
            }
        };

        $items = $subject->items(
            (new ServerRequest('https://www.acme.com/'))->withAttribute('site', $site),
            GeneralUtility::makeInstance(LocalizationUtility::class),
            self::TABLE,
            self::FIELD,
        );

        $this->assertSame([], $items);
        $parameters = RecordingItemsProcFunc::$parameters;
        $this->assertIsArray($parameters, 'The itemsProcFunc handler was not called.');
        $this->assertSame(
            ['items', 'config', 'TSconfig', 'table', 'row', 'field', 'effectivePid', 'site'],
            array_keys($parameters),
        );
        $this->assertSame([], $parameters['row']);
        $this->assertSame([], $parameters['items']);
        $this->assertSame(self::TABLE, $parameters['table']);
        $this->assertSame(self::FIELD, $parameters['field']);
        $this->assertNull($parameters['TSconfig']);
        $this->assertSame(1, $parameters['effectivePid']);
        $this->assertSame($site, $parameters['site']);
    }
}
