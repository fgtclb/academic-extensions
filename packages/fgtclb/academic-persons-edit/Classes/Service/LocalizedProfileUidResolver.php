<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Maps the profile uid the frontend works with onto the uid of the database row a
 * write of the requested site language has to address.
 *
 * Extbase hands the controller the record its language overlay resolved to, which
 * is the translation in a language that has one and the default-language record in
 * a language that has not. The image endpoints write through the DataHandler and
 * therefore need the real row:
 *
 * - the record already is in the requested language: its own uid;
 * - a visible translation of it exists: the translation's uid;
 * - no translation row exists at all: the default-language uid - the same row the
 *   text endpoints write through Extbase in that situation, so one language never
 *   silently edits a record another one cannot see;
 * - the record is gone, or the translation exists but is hidden: `null`, which the
 *   caller answers with a 404. A hidden translation is a row the visitor may not
 *   see, and writing the default record instead would edit a different profile
 *   than the one on screen.
 *
 * Only live rows are resolved. `ProfileImageRelationWriter` refuses a workspace
 * version uid, and the editor has no workspace story of its own: the uid it hands
 * on always addresses the live record.
 *
 * @internal not part of public API.
 */
final readonly class LocalizedProfileUidResolver
{
    private const TABLE = 'tx_academicpersons_domain_model_profile';

    public function __construct(private ConnectionPool $connectionPool) {}

    public function resolve(int $profileUid, int $languageId): ?int
    {
        if ($profileUid <= 0) {
            return null;
        }
        $record = $this->findRecord(
            static function ($queryBuilder) use ($profileUid) {
                return [
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($profileUid, Connection::PARAM_INT),
                    ),
                ];
            },
        );
        if ($record === null || $record['hidden']) {
            return null;
        }
        if ($languageId <= 0 || $record['sys_language_uid'] === $languageId) {
            return $record['uid'];
        }
        $defaultProfileUid = $record['l10n_parent'] > 0 ? $record['l10n_parent'] : $record['uid'];
        $translation = $this->findRecord(
            static function ($queryBuilder) use ($defaultProfileUid, $languageId) {
                return [
                    $queryBuilder->expr()->eq(
                        'l10n_parent',
                        $queryBuilder->createNamedParameter($defaultProfileUid, Connection::PARAM_INT),
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT),
                    ),
                ];
            },
        );
        if ($translation === null) {
            // The language carries no row of its own: write the default record, exactly
            // as the Extbase based text endpoints do for the same profile.
            return $defaultProfileUid;
        }
        return $translation['hidden'] ? null : $translation['uid'];
    }

    /**
     * Reads one live profile row with the deleted restriction only, so that a hidden
     * row is told apart from a missing one - the two mean different things here.
     *
     * @param \Closure(\TYPO3\CMS\Core\Database\Query\QueryBuilder): list<\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression|string> $constraints
     * @return array{uid: int, sys_language_uid: int, l10n_parent: int, hidden: bool}|null
     */
    private function findRecord(\Closure $constraints): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));
        $record = $queryBuilder
            ->select('uid', 'sys_language_uid', 'l10n_parent', 'hidden')
            ->from(self::TABLE)
            ->where(...$constraints($queryBuilder))
            ->orderBy('uid')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if ($record === false) {
            return null;
        }
        return [
            'uid' => (int)$record['uid'],
            'sys_language_uid' => (int)$record['sys_language_uid'],
            'l10n_parent' => (int)$record['l10n_parent'],
            'hidden' => (bool)$record['hidden'],
        ];
    }
}
