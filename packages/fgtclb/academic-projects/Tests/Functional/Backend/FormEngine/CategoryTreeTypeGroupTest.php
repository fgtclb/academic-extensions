<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\FormSelectTreeAjaxController;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The category tree of the project plugins, fetched the way FormEngine fetches it
 * when an editor opens the field: through the select tree AJAX controller.
 *
 * The plugins use the categories of the `projects` group only. The demand reads the
 * selection with `CategoryRepository::getByDatabaseFields('projects', ...)`, which
 * drops every category of another type silently, so a tree that offered them let an
 * editor select a filter the frontend never applied. The fixture holds one category
 * of four project types (1 to 4), a plain category (5) and one category each of a
 * type of the partners, programs and jobs extensions (6 to 8).
 *
 * A category of the group below a parent outside of it stays reachable: the tree
 * shows a category only together with its ancestors, so the parent is offered as
 * well (9 above 10, 12 above 13), while another category below the same parent is
 * not (11). A deleted category (14) and a translation (15) are not offered.
 */
final class CategoryTreeTypeGroupTest extends AbstractAcademicProjectsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryTreeTypeGroup/categories.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG'], $GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function pluginProvider(): array
    {
        return [
            'project list' => ['academicprojects_projectlist'],
            'selected projects' => ['academicprojects_projectlistsingle'],
        ];
    }

    #[Test]
    #[DataProvider('pluginProvider')]
    public function theCategoryTreeOffersTheCategoriesOfTheProjectsGroupOnly(string $cType): void
    {
        $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->update('tt_content', ['CType' => $cType], ['uid' => 1]);

        $this->assertSame([1, 2, 3, 4, 9, 10, 12, 13], $this->offeredCategoryUids());
    }

    /**
     * @return list<int>
     */
    private function offeredCategoryUids(): array
    {
        $row = $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->select(['*'], 'tt_content', ['uid' => 1])
            ->fetchAssociative();
        $this->assertIsArray($row);
        $dataStructureIdentifier = GeneralUtility::makeInstance(FlexFormTools::class)->getDataStructureIdentifier(
            $GLOBALS['TCA']['tt_content']['columns']['pi_flexform'],
            'tt_content',
            'pi_flexform',
            $row,
        );

        $request = (new ServerRequest('https://localhost/typo3/ajax/record/tree/fetchData'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams([
                'tableName' => 'tt_content',
                'fieldName' => 'pi_flexform',
                'uid' => 1,
                'command' => 'edit',
                'recordTypeValue' => $row['CType'],
                'dataStructureIdentifier' => $dataStructureIdentifier,
                'flexFormSheetName' => 'sDEF',
                'flexFormFieldName' => 'settings.categories',
                'flexFormContainerName' => '',
                'flexFormContainerIdentifier' => '',
                'flexFormContainerFieldName' => '',
                'flexFormSectionContainerIsNew' => '',
            ]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $response = GeneralUtility::makeInstance(FormSelectTreeAjaxController::class)->fetchDataAction($request);
        $nodes = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($nodes);

        $uids = [];
        foreach ($nodes as $node) {
            $uid = (int)($node['identifier'] ?? 0);
            if ($uid > 0) {
                $uids[] = $uid;
            }
        }
        sort($uids);
        return $uids;
    }
}
