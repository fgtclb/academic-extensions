<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Backend\FormDataProvider;

use FGTCLB\AcademicBase\Backend\FormDataProvider\KeepCurrentContentTypeSelectable;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compiles the backend form of a content element the way FormEngine does when an
 * editor opens it, through the complete `tcaDatabaseRecord` data provider group.
 *
 * Every academic extension hides its content types in its always-loaded page
 * TSconfig. Core `TcaSelectItems` then drops the stored type of an existing record
 * from the row and, because the value was removed by TSconfig, also refuses to show
 * its "invalid value" option - the select renders its first option instead and a save
 * rewrites the type. The fixture extension `test_hidden_content_types` reproduces that
 * with one type inside and one outside the `academic` item group.
 *
 * @see KeepCurrentContentTypeSelectable
 */
final class KeepCurrentContentTypeSelectableTest extends AbstractAcademicBaseTestCase
{
    private const HIDDEN_ACADEMIC_TYPE = 'testhidden_academic';
    private const HIDDEN_OTHER_TYPE = 'testhidden_other';

    protected array $testExtensionsToLoad = [
        'fgtclb/academic-base',
        'tests/hidden-content-types',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/HiddenContentTypes.csv');
        $connection = $this->getConnectionPool()->getConnectionForTable('pages');
        // Brings the type back from the installation wide `removeItems`, then hides it
        // again with `keepItems` - so only `keepItems` hides it on this page.
        $connection->update(
            'pages',
            [
                'TSconfig' => implode("\n", [
                    'TCEFORM.tt_content.CType.removeItems := removeFromList(' . self::HIDDEN_ACADEMIC_TYPE . ')',
                    'TCEFORM.tt_content.CType.keepItems = text,header',
                ]),
            ],
            ['uid' => 3],
        );
        // What a component set does for its own content types.
        $connection->update(
            'pages',
            ['TSconfig' => 'TCEFORM.tt_content.CType.removeItems := removeFromList(' . self::HIDDEN_ACADEMIC_TYPE . ')'],
            ['uid' => 4],
        );
        // Hidden by the installation wide `removeItems`, and additionally disallowed in
        // column 0 by the backend layout (`disallowedContentTypes`, TYPO3 v14 only).
        $connection->update(
            'pages',
            [
                'backend_layout' => 'pagets__restricted',
                'TSconfig' => implode("\n", [
                    'mod.web_layout.BackendLayouts.restricted {',
                    '  title = Restricted',
                    '  config.backend_layout {',
                    '    colCount = 1',
                    '    rowCount = 1',
                    '    rows.1.columns.1 {',
                    '      name = Main',
                    '      colPos = 0',
                    '      disallowedContentTypes = ' . self::HIDDEN_ACADEMIC_TYPE,
                    '    }',
                    '  }',
                    '}',
                ]),
            ],
            ['uid' => 5],
        );
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function storedAcademicTypeHiddenByRemoveItemsStaysSelected(): void
    {
        $result = $this->compileEditForm(1);

        $this->assertSame([self::HIDDEN_ACADEMIC_TYPE], $result['databaseRow']['CType']);
        $items = $this->findContentTypeItems($result, self::HIDDEN_ACADEMIC_TYPE);
        $this->assertCount(1, $items);
        $this->assertSame('Hidden academic element (not enabled on this page)', $items[0]['label']);
        $this->assertSame('content-text', $items[0]['icon']);
    }

    #[Test]
    public function storedAcademicTypeHiddenByKeepItemsStaysSelected(): void
    {
        $result = $this->compileEditForm(4);

        $this->assertSame([self::HIDDEN_ACADEMIC_TYPE], $result['databaseRow']['CType']);
        $items = $this->findContentTypeItems($result, self::HIDDEN_ACADEMIC_TYPE);
        $this->assertCount(1, $items);
        $this->assertSame('Hidden academic element (not enabled on this page)', $items[0]['label']);
    }

    /**
     * TYPO3 v14 restricts the content types per backend layout column in
     * `TcaTtContentCtypeItemsRestrictionByBackendLayout`, which returns early for an
     * empty row value. The provider therefore has to run before it: then a type the
     * column disallows is kept, but labelled by core as an invalid value, exactly as for
     * a type that is not hidden by page TSconfig. Run after it, the suffix label would
     * show instead.
     */
    #[Test]
    #[Group('not-core-13')]
    public function storedAcademicTypeDisallowedByTheBackendLayoutGetsCoreHandling(): void
    {
        $result = $this->compileEditForm(6);

        $this->assertSame([self::HIDDEN_ACADEMIC_TYPE], $result['databaseRow']['CType']);
        $items = $this->findContentTypeItems($result, self::HIDDEN_ACADEMIC_TYPE);
        $this->assertCount(1, $items);
        $this->assertSame(
            sprintf(
                $GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'),
                self::HIDDEN_ACADEMIC_TYPE,
            ),
            $items[0]['label'],
        );
    }

    #[Test]
    public function storedAcademicTypeEnabledOnThePageIsOfferedUnchanged(): void
    {
        $result = $this->compileEditForm(5);

        $this->assertSame([self::HIDDEN_ACADEMIC_TYPE], $result['databaseRow']['CType']);
        $items = $this->findContentTypeItems($result, self::HIDDEN_ACADEMIC_TYPE);
        $this->assertCount(1, $items);
        $this->assertSame('Hidden academic element', $items[0]['label']);
    }

    /**
     * The new record starts with the hidden type as its default value, the way a link
     * with `defVals` or `TCAdefaults` would open it - otherwise it would start as a
     * text element and the case would not reach the provider at all.
     */
    #[Test]
    public function newContentElementIsNotOfferedTheHiddenAcademicType(): void
    {
        $result = $this->compileForm('new', 2, ['tt_content' => ['CType' => self::HIDDEN_ACADEMIC_TYPE]]);

        $this->assertSame(self::HIDDEN_ACADEMIC_TYPE, $result['recordTypeValue']);
        $this->assertSame([], $result['databaseRow']['CType']);
        $this->assertSame([], $this->findContentTypeItems($result, self::HIDDEN_ACADEMIC_TYPE));
    }

    /**
     * A stored type can only be missing from the items for another reason than page
     * TSconfig through user permissions, and TYPO3 refuses to open such a record in the
     * first place. So this guard is pinned by calling the provider directly.
     */
    #[Test]
    public function academicTypeMissingForAnotherReasonIsLeftAlone(): void
    {
        $result = $this->createProcessedResult(items: [['label' => 'Text', 'value' => 'text']], pageTsConfig: []);

        $this->assertSame($result, (new KeepCurrentContentTypeSelectable())->addData($result));
    }

    #[Test]
    public function academicTypeStillAmongTheItemsIsNotAddedTwice(): void
    {
        $result = $this->createProcessedResult(
            items: [['label' => 'Hidden academic element', 'value' => self::HIDDEN_ACADEMIC_TYPE]],
            pageTsConfig: ['removeItems' => self::HIDDEN_ACADEMIC_TYPE],
        );

        $this->assertSame($result, (new KeepCurrentContentTypeSelectable())->addData($result));
    }

    #[Test]
    public function otherContentElementOnThePageIsNotOfferedTheHiddenAcademicType(): void
    {
        $result = $this->compileEditForm(2);

        $this->assertSame(['text'], $result['databaseRow']['CType']);
        $this->assertSame([], $this->findContentTypeItems($result, self::HIDDEN_ACADEMIC_TYPE));
    }

    /**
     * Pins core behaviour for a type outside the `academic` group: the stored value is
     * dropped and no option is offered for it - exactly what TYPO3 renders without
     * academic_base.
     */
    #[Test]
    public function storedTypeHiddenOutsideTheAcademicGroupKeepsCoreBehaviour(): void
    {
        $result = $this->compileEditForm(3);

        $this->assertSame([], $result['databaseRow']['CType']);
        $this->assertSame([], $this->findContentTypeItems($result, self::HIDDEN_OTHER_TYPE));
    }

    /**
     * Pins that DataHandler stores the hidden type: it validates a select without a
     * `foreign_table` against neither the items nor page TSconfig. This is what makes
     * keeping the option in the form sufficient.
     */
    #[Test]
    public function unchangedSaveKeepsTheHiddenAcademicType(): void
    {
        $dataHandler = $this->processDatamap([
            'tt_content' => [
                1 => [
                    'CType' => self::HIDDEN_ACADEMIC_TYPE,
                    'header' => 'Hidden academic element, saved',
                ],
            ],
        ]);

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertSame(
            ['CType' => self::HIDDEN_ACADEMIC_TYPE, 'header' => 'Hidden academic element, saved'],
            $this->getContentElement(1),
        );
    }

    #[Test]
    public function deliberateTypeChangeIsStoredAndHidesTheAcademicTypeAgain(): void
    {
        $dataHandler = $this->processDatamap([
            'tt_content' => [
                1 => [
                    'CType' => 'text',
                ],
            ],
        ]);

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertSame('text', $this->getContentElement(1)['CType']);

        $result = $this->compileEditForm(1);

        $this->assertSame(['text'], $result['databaseRow']['CType']);
        $this->assertSame([], $this->findContentTypeItems($result, self::HIDDEN_ACADEMIC_TYPE));
    }

    /**
     * @return array<string, mixed>
     */
    private function compileEditForm(int $uid): array
    {
        return $this->compileForm('edit', $uid);
    }

    /**
     * The shape of the form data right after `TcaSelectItems`, for an existing record
     * that stores the hidden academic type.
     *
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $pageTsConfig The `TCEFORM.tt_content.CType.` part
     * @return array<string, mixed>
     */
    private function createProcessedResult(array $items, array $pageTsConfig): array
    {
        return [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'recordTypeValue' => self::HIDDEN_ACADEMIC_TYPE,
            'databaseRow' => ['uid' => 1, 'pid' => 2, 'CType' => []],
            'pageTsConfig' => ['TCEFORM.' => ['tt_content.' => ['CType.' => $pageTsConfig]]],
            'processedTca' => ['columns' => ['CType' => ['config' => ['items' => $items]]]],
        ];
    }

    /**
     * @param int $uid The record uid for "edit", the page uid for "new"
     * @param array<string, array<string, mixed>> $defaultValues
     * @return array<string, mixed>
     */
    private function compileForm(string $command, int $uid, array $defaultValues = []): array
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tt_content',
                'vanillaUid' => $uid,
                'command' => $command,
                'defaultValues' => $defaultValues,
            ],
            $this->get(TcaDatabaseRecord::class),
        );
    }

    /**
     * @param array<string, mixed> $result
     * @return list<array<string, mixed>>
     */
    private function findContentTypeItems(array $result, string $value): array
    {
        return array_values(array_filter(
            $result['processedTca']['columns']['CType']['config']['items'] ?? [],
            static fn(array $item): bool => ($item['value'] ?? null) === $value,
        ));
    }

    /**
     * @param array<string, array<int|string, array<string, mixed>>> $datamap
     */
    private function processDatamap(array $datamap): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, []);
        $dataHandler->process_datamap();

        return $dataHandler;
    }

    /**
     * @return array{CType: string, header: string}
     */
    private function getContentElement(int $uid): array
    {
        $row = $this->getConnectionPool()->getConnectionForTable('tt_content')->select(
            ['CType', 'header'],
            'tt_content',
            ['uid' => $uid],
        )->fetchAssociative();
        $this->assertIsArray($row);

        return ['CType' => (string)$row['CType'], 'header' => (string)$row['header']];
    }
}
