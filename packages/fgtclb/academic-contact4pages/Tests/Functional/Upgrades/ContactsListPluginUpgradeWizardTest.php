<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Upgrades;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\AcademicContacts4pages\Upgrades\ContactsListPluginUpgradeWizard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use TYPO3\CMS\Install\Updates\UpgradeWizardRegistry;

/**
 * The fixture holds content elements of the type version 1.x stored for the page
 * contacts plugin: in the default language (uid 1), translated (uid 2), hidden
 * (uid 3), deleted (uid 4) and as a workspace version (uid 5). Next to them an
 * element that already carries the type of 2.0 (uid 6) and an unrelated one
 * (uid 7), which both have to stay as they are.
 *
 * Its backend user groups are allowed to edit the old type: next to other types
 * (uid 1), next to the new type already (uid 2), in the format of TYPO3 v11
 * (uid 3) and as a deleted group (uid 4). Group 5 is allowed a longer type that
 * starts with the old one, and has to stay as it is.
 */
final class ContactsListPluginUpgradeWizardTest extends AbstractAcademicContacts4PagesTestCase
{
    private const IDENTIFIER = 'academicContacts4pages_pluginUpgradeWizard';
    private const VERSION_1_TYPE = 'academiccontacts4pages_contactslist';
    private const VERSION_1_GROUP_UIDS = [1, 2, 3, 4];
    private const FIXTURES = __DIR__ . '/Fixtures/ContactsListPlugin/';

    /**
     * The wizard of 2.0.0 to 2.3.4, which `upgrade:run` marked as done because it
     * never found anything. The class no longer exists.
     */
    private const PREVIOUS_IDENTIFIER = 'academicContact4pages_pluginUpgradeWizard';
    private const PREVIOUS_CLASS_NAME = 'FGTCLB\\AcademicContacts4pages\\Upgrades\\PluginUpgradeWizard';

    /**
     * The migrated state holds group 5, allowed a longer type that starts with
     * the old one. It does not make the wizard necessary.
     */
    #[Test]
    public function updateIsNotNecessaryWithoutRowsOfVersion1(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'migratedContentElements.csv');

        $this->assertFalse($this->getSubject()->updateNecessary());
    }

    /**
     * @return \Generator<string, array{table: string, uid: int}>
     */
    public static function rowsOfVersion1(): \Generator
    {
        yield 'content element in the default language' => ['table' => 'tt_content', 'uid' => 1];
        yield 'translated content element' => ['table' => 'tt_content', 'uid' => 2];
        yield 'hidden content element' => ['table' => 'tt_content', 'uid' => 3];
        yield 'deleted content element' => ['table' => 'tt_content', 'uid' => 4];
        yield 'content element in a workspace' => ['table' => 'tt_content', 'uid' => 5];
        yield 'backend user group allowed the old type' => ['table' => 'be_groups', 'uid' => 1];
        yield 'backend user group allowed both types' => ['table' => 'be_groups', 'uid' => 2];
        yield 'backend user group in the format of TYPO3 v11' => ['table' => 'be_groups', 'uid' => 3];
        yield 'deleted backend user group' => ['table' => 'be_groups', 'uid' => 4];
    }

    /**
     * Each kind of row is enough on its own for the wizard to be offered.
     */
    #[DataProvider('rowsOfVersion1')]
    #[Test]
    public function updateIsNecessaryForEveryRowOfVersion1(string $table, int $uid): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'version1ContentElements.csv');
        $this->keepOnlyRowOfVersion1($table, $uid);

        $this->assertTrue($this->getSubject()->updateNecessary());
    }

    #[Test]
    public function executeUpdateMigratesEveryRowOfVersion1AndNothingElse(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'version1ContentElements.csv');

        $this->assertTrue($this->getSubject()->executeUpdate());

        $this->assertCSVDataSet(self::FIXTURES . 'migratedContentElements.csv');
    }

    #[Test]
    public function nothingIsLeftToDoAfterTheUpdate(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'version1ContentElements.csv');
        $this->assertTrue($this->getSubject()->updateNecessary());

        $this->getSubject()->executeUpdate();

        $this->assertFalse($this->getSubject()->updateNecessary());
    }

    #[Test]
    public function theWizardIsRegisteredUnderItsOwnIdentifier(): void
    {
        $registry = $this->getUpgradeWizardRegistry();

        $this->assertTrue($registry->hasUpgradeWizard(self::IDENTIFIER));
        $this->assertInstanceOf(ContactsListPluginUpgradeWizard::class, $registry->getUpgradeWizard(self::IDENTIFIER));
        $this->assertFalse($registry->hasUpgradeWizard(self::PREVIOUS_IDENTIFIER));
    }

    /**
     * The core records a wizard as done by its class name. An installation that ran
     * the previous wizard carries that flag, and it must not hide this one.
     */
    #[Test]
    public function theWizardIsOfferedWhereThePreviousOneIsMarkedDone(): void
    {
        $registry = $this->get(Registry::class);
        $this->assertInstanceOf(Registry::class, $registry);
        $registry->set('installUpdate', self::PREVIOUS_CLASS_NAME, 1);
        $upgradeWizardsService = $this->getUpgradeWizardsService();

        $this->assertFalse($upgradeWizardsService->isWizardDone(self::IDENTIFIER));

        // The lookup itself works: marking this wizard done is seen.
        $registry->set('installUpdate', ContactsListPluginUpgradeWizard::class, 1);
        $this->assertTrue($upgradeWizardsService->isWizardDone(self::IDENTIFIER));
    }

    /**
     * Removes every row of version 1.x from both tables, except the given one.
     */
    private function keepOnlyRowOfVersion1(string $table, int $uid): void
    {
        $keptContentUid = $table === 'tt_content' ? $uid : 0;
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->delete('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter(self::VERSION_1_TYPE)),
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($keptContentUid, Connection::PARAM_INT)),
            )
            ->executeStatement();

        $groupUids = array_values(array_diff(self::VERSION_1_GROUP_UIDS, $table === 'be_groups' ? [$uid] : []));
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('be_groups');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->delete('be_groups')
            ->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->quoteArrayBasedValueListToIntegerList($groupUids)),
            )
            ->executeStatement();
    }

    private function getSubject(): ContactsListPluginUpgradeWizard
    {
        $subject = $this->get(ContactsListPluginUpgradeWizard::class);
        $this->assertInstanceOf(ContactsListPluginUpgradeWizard::class, $subject);
        return $subject;
    }

    private function getUpgradeWizardRegistry(): UpgradeWizardRegistry
    {
        $registry = $this->get(UpgradeWizardRegistry::class);
        $this->assertInstanceOf(UpgradeWizardRegistry::class, $registry);
        return $registry;
    }

    private function getUpgradeWizardsService(): UpgradeWizardsService
    {
        $service = $this->get(UpgradeWizardsService::class);
        $this->assertInstanceOf(UpgradeWizardsService::class, $service);
        return $service;
    }
}
