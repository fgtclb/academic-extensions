<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Upgrades;

use FGTCLB\AcademicPersons\Profile\FrontendUserPhoneNumberTypeResolver;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('academicPersons_MigrateImportedPhoneNumberTypes')]
final class MigrateImportedPhoneNumberTypesUpgradeWizard implements UpgradeWizardInterface
{
    private const TABLE = 'tx_academicpersons_domain_model_phone_number';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly FrontendUserPhoneNumberTypeResolver $phoneNumberTypeResolver,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate imported frontend-user phone number types';
    }

    public function getDescription(): string
    {
        return 'Normalizes imported telephone identifiers and replaces legacy telephone and fax types.';
    }

    public function getPrerequisites(): array
    {
        return [DatabaseUpdatedPrerequisite::class];
    }

    public function updateNecessary(): bool
    {
        return $this->getActions() !== [];
    }

    public function executeUpdate(): bool
    {
        foreach ($this->getActions() as $action) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
            $queryBuilder->update(self::TABLE);
            foreach ($action['fields'] as $field => $value) {
                $queryBuilder->set($field, $value);
            }
            $queryBuilder
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($action['uid'], Connection::PARAM_INT),
                    ),
                )
                ->executeStatement();
        }
        return true;
    }

    /**
     * @return list<array{uid: int, fields: array<string, string>}>
     */
    private function getActions(): array
    {
        $rows = $this->getImportedPhoneNumberRows();
        $canonicalIdentifiers = [];
        foreach ($rows as $row) {
            $identifier = (string)$row['import_identifier'];
            if (preg_match('/^telephone:fe_users:[0-9]+$/D', $identifier) === 1) {
                $canonicalIdentifiers[$this->buildContractIdentifier((int)$row['contract'], $identifier)] = true;
            }
        }

        $actions = [];
        foreach ($rows as $row) {
            $identifier = (string)$row['import_identifier'];
            if (preg_match('/^(phone|fax):fe_users:([0-9]+)$/D', $identifier, $matches) !== 1) {
                continue;
            }
            $source = $matches[1];
            $frontendUserUid = $matches[2];
            $fields = [];
            if ($source === 'phone') {
                $canonicalIdentifier = 'telephone:fe_users:' . $frontendUserUid;
                if (!isset($canonicalIdentifiers[$this->buildContractIdentifier((int)$row['contract'], $canonicalIdentifier)])) {
                    $fields['import_identifier'] = $canonicalIdentifier;
                }
            }
            if ((string)$row['type'] === $source && !$this->phoneNumberTypeResolver->isSelectable($source)) {
                $fields['type'] = $source === 'phone'
                    ? $this->phoneNumberTypeResolver->getTelephoneNumberType()
                    : $this->phoneNumberTypeResolver->getFaxNumberType();
            }
            if ($fields !== []) {
                $actions[] = [
                    'uid' => (int)$row['uid'],
                    'fields' => $fields,
                ];
            }
        }
        return $actions;
    }

    /**
     * @return list<array{uid: int|string, contract: int|string, type: string, import_identifier: string}>
     */
    private function getImportedPhoneNumberRows(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $likeConstraints = [];
        foreach (['phone:fe_users:', 'fax:fe_users:', 'telephone:fe_users:'] as $prefix) {
            $likeConstraints[] = $queryBuilder->expr()->like(
                'import_identifier',
                $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($prefix) . '%'),
            );
        }
        $rows = $queryBuilder
            ->select('uid', 'contract', 'type', 'import_identifier')
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->or(...$likeConstraints))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        /** @var list<array{uid: int|string, contract: int|string, type: string, import_identifier: string}> $rows */
        return $rows;
    }

    private function buildContractIdentifier(int $contractUid, string $importIdentifier): string
    {
        return $contractUid . ':' . $importIdentifier;
    }
}
