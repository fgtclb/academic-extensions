<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Upgrades;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\AcademicContacts4pages\Upgrades\SeedContactSecondarySortingUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;

/**
 * The fixture holds contacts of two contracts and of two contacts roles, arranged
 * so that the two groupings differ from each other and both contradict uid order.
 *
 * Contract 1 holds uid 3 (`sorting` 1), uids 1 and 2 (both `sorting` 2, settled by
 * uid) and the deleted uid 7 (`sorting` 3); contract 2 holds uid 5 (`sorting` 5)
 * before uid 4 (`sorting` 7). Role 1 holds uids 3, 1, 7 and 5, role 2 uids 2 and 4.
 * Uid 6 carries neither and has to stay at 0 in both columns.
 */
final class SeedContactSecondarySortingUpgradeWizardTest extends AbstractAcademicContacts4PagesTestCase
{
    private const TABLE = 'tx_academiccontacts4pages_domain_model_contact';
    private const FIXTURE = __DIR__ . '/Fixtures/SeedContactSecondarySorting/contactsOfTwoContractsAndTwoRoles.csv';

    #[Test]
    public function updateIsNecessaryWhileAContactOfAContractOrRoleIsUnseeded(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertTrue($this->getSubject()->updateNecessary());
    }

    #[Test]
    public function executeUpdateNumbersTheContactsOfEveryContract(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertTrue($this->getSubject()->executeUpdate());

        $this->assertSame(
            [1 => 2, 2 => 3, 3 => 1, 4 => 2, 5 => 1, 6 => 0, 7 => 4],
            $this->fetchSortingByUid('contract_sorting'),
        );
    }

    #[Test]
    public function executeUpdateNumbersTheContactsOfEveryContactsRole(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertTrue($this->getSubject()->executeUpdate());

        $this->assertSame(
            [1 => 2, 2 => 1, 3 => 1, 4 => 2, 5 => 4, 6 => 0, 7 => 3],
            $this->fetchSortingByUid('role_sorting'),
        );
    }

    #[Test]
    public function nothingIsLeftToDoAfterTheUpdate(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();

        $this->assertFalse($this->getSubject()->updateNecessary());
    }

    /**
     * Running it twice writes the same numbers: the wizard derives them from
     * `sorting`, which it does not touch.
     */
    #[Test]
    public function executeUpdateIsIdempotent(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();
        $afterFirstRun = [
            $this->fetchSortingByUid('contract_sorting'),
            $this->fetchSortingByUid('role_sorting'),
        ];

        $this->getSubject()->executeUpdate();

        $this->assertSame($afterFirstRun, [
            $this->fetchSortingByUid('contract_sorting'),
            $this->fetchSortingByUid('role_sorting'),
        ]);
    }

    /**
     * An installation that has no contact with a contract or a contacts role at all
     * is not offered the wizard.
     */
    #[Test]
    public function updateIsNotNecessaryWithoutContactsCarryingAContractOrRole(): void
    {
        $this->assertFalse($this->getSubject()->updateNecessary());
    }

    /**
     * A second run **appends**, it does not renumber: the ranks the first run wrote -
     * and any arrangement an editor made on top of them - are left alone, and only a
     * contact that has none is given one, behind them.
     */
    #[Test]
    public function executeUpdateAppendsWithoutResettingWhatIsAlreadyRanked(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();
        $contractAfterFirstRun = $this->fetchSortingByUid('contract_sorting');
        $roleAfterFirstRun = $this->fetchSortingByUid('role_sorting');
        // The one contact without a contract and a role joins both, the way the
        // DataHandler hook would not have to: written straight to the database, so
        // nothing ranks it.
        $this->assignParentsWithoutRank(6, 1, 1);

        $this->getSubject()->executeUpdate();

        $this->assertSame([1 => 2, 2 => 3, 3 => 1, 4 => 2, 5 => 1, 6 => 5, 7 => 4], $this->fetchSortingByUid('contract_sorting'));
        $this->assertSame([1 => 2, 2 => 1, 3 => 1, 4 => 2, 5 => 4, 6 => 5, 7 => 3], $this->fetchSortingByUid('role_sorting'));
        unset($contractAfterFirstRun[6], $roleAfterFirstRun[6]);
        $this->assertSame(
            [$contractAfterFirstRun, $roleAfterFirstRun],
            [
                array_diff_key($this->fetchSortingByUid('contract_sorting'), [6 => true]),
                array_diff_key($this->fetchSortingByUid('role_sorting'), [6 => true]),
            ],
            'The second run changed a rank it had already written.',
        );
    }

    /**
     * Writes the parent columns straight to the database, without the DataHandler, so
     * the row ends up in the state an installation is in before the wizard has run.
     */
    private function assignParentsWithoutRank(int $uid, int $contractUid, int $roleUid): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE);
        $connection->update(
            self::TABLE,
            ['contract' => $contractUid, 'contract_sorting' => 0, 'role' => $roleUid, 'role_sorting' => 0],
            ['uid' => $uid],
        );
    }

    private function getSubject(): SeedContactSecondarySortingUpgradeWizard
    {
        $subject = $this->get(SeedContactSecondarySortingUpgradeWizard::class);
        $this->assertInstanceOf(SeedContactSecondarySortingUpgradeWizard::class, $subject);
        return $subject;
    }

    /**
     * @return array<int, int>
     */
    private function fetchSortingByUid(string $sortField): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', $sortField)
            ->from(self::TABLE)
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $sortingByUid = [];
        foreach ($rows as $row) {
            $sortingByUid[(int)$row['uid']] = (int)$row[$sortField];
        }
        return $sortingByUid;
    }
}
