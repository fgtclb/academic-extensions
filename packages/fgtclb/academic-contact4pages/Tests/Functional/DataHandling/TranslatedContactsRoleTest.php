<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\DataHandling;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A translated page contact keeps the contacts role of its default language record:
 * the `role` select of the contact is `l10n_mode` `exclude`, and the contacts role
 * list of the frontend and the `role_sorting` rank are kept on the default language
 * role.
 *
 * The inline list of contacts on the role was `l10n_mode` `exclude` as well. For an
 * inline relation that makes the DataHandler synchronize the list into every
 * translation of the role, which points the translated children at the translated
 * parent: saving the role, in its default language or as a translation, wrote the
 * uid of the role translation into the `role` of every translated contact and
 * renumbered their `role_sorting`. The two settings contradicted each other.
 *
 * The fixture is the state a translated page leaves: contacts 3 and 4 translate
 * contacts 1 and 2, keep role 1 and are ranked behind them in its list.
 */
final class TranslatedContactsRoleTest extends AbstractAcademicContacts4PagesTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const TABLE_ROLE = 'tx_academiccontacts4pages_domain_model_role';
    private const TABLE_CONTACT = 'tx_academiccontacts4pages_domain_model_contact';

    /**
     * The contacts as the fixture stores them. Every test asserts the translated ones
     * against this.
     */
    private const TRANSLATED_CONTACTS = [
        ['uid' => 3, 'deleted' => 0, 'sys_language_uid' => 1, 'l10n_parent' => 1, 'page' => 12, 'role' => 1, 'role_sorting' => 3],
        ['uid' => 4, 'deleted' => 0, 'sys_language_uid' => 1, 'l10n_parent' => 2, 'page' => 12, 'role' => 1, 'role_sorting' => 4],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            identifier: 'translated-contacts-role-test',
            site: $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedContactsRole/translatedRoleWithTranslatedContacts.csv');
        $this->setUpBackendUser(1);
        // The DataHandler and FormEngine read the language service from $GLOBALS['LANG'].
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG'], $GLOBALS['TYPO3_REQUEST']);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * What the backend form submits for a translated role: its own fields, and no
     * contacts list, since that list is not offered on a translation.
     */
    #[Test]
    public function savingATranslatedRoleKeepsTheTranslatedContacts(): void
    {
        $this->processDataMap([self::TABLE_ROLE => [2 => ['name' => 'Leitung des Büros']]]);

        $this->assertSame(self::TRANSLATED_CONTACTS, $this->fetchContacts(1));
        $this->assertSame([], $this->fetchContacts(2), 'A contact was moved to the role translation.');
    }

    #[Test]
    public function savingTheDefaultLanguageRoleKeepsTheTranslatedContacts(): void
    {
        $this->processDataMap([self::TABLE_ROLE => [1 => ['name' => 'Office lead']]]);

        $this->assertSame(self::TRANSLATED_CONTACTS, $this->fetchContacts(1));
        $this->assertSame([], $this->fetchContacts(2), 'A contact was moved to the role translation.');
    }

    /**
     * Rearranging the contacts of the default language role still works, and still
     * leaves the translations with the role they have.
     */
    #[Test]
    public function rearrangingTheContactsOfTheDefaultLanguageRoleKeepsTheTranslatedContacts(): void
    {
        $this->processDataMap([self::TABLE_ROLE => [1 => ['contacts' => '2,1,3,4']]]);

        $this->assertSame([2, 1], array_column($this->fetchContacts(1, 0), 'uid'));
        $this->assertSame(self::TRANSLATED_CONTACTS, $this->fetchContacts(1));
        $this->assertSame([], $this->fetchContacts(2), 'A contact was moved to the role translation.');
    }

    /**
     * Pins the `displayCond` of the list. Without `l10n_mode` it is the only thing
     * that keeps the form of a translation from rendering the list and submitting
     * it, and `writeForeignField()` points every contact of a submitted list at
     * the role translation.
     */
    #[Test]
    public function theContactsListIsOfferedOnTheDefaultLanguageRoleOnly(): void
    {
        $this->assertArrayHasKey('contacts', $this->compileRoleForm(1));
        $this->assertArrayNotHasKey('contacts', $this->compileRoleForm(2));
    }

    /**
     * @param array<string, array<int, array<string, int|string>>> $dataMap
     */
    private function processDataMap(array $dataMap): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    /**
     * The contacts of one role in the order of its list: the translated ones, or the
     * ones of the given language.
     *
     * @return list<array<string, int>>
     */
    private function fetchContacts(int $roleUid, ?int $languageUid = null): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTACT);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('uid', 'deleted', 'sys_language_uid', 'l10n_parent', 'page', 'role', 'role_sorting')
            ->from(self::TABLE_CONTACT)
            ->where($queryBuilder->expr()->eq('role', $queryBuilder->createNamedParameter($roleUid, Connection::PARAM_INT)))
            ->orderBy('role_sorting')
            ->addOrderBy('uid');
        if ($languageUid !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)),
            );
        } else {
            $queryBuilder->andWhere($queryBuilder->expr()->gt('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)));
        }
        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();
        return array_map(
            static fn(array $row): array => array_map(intval(...), $row),
            $rows,
        );
    }

    /**
     * @return array<string, mixed> The columns the backend form of the role offers.
     */
    private function compileRoleForm(int $uid): array
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => self::TABLE_ROLE,
                'vanillaUid' => $uid,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );

        return $result['processedTca']['columns'];
    }
}
