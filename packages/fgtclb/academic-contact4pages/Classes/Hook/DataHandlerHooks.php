<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Hook;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Removes translated contacts pointing at pages that are not translated into the
 * contact's language (ACE-484 / ACE-103 / HNEE-1249).
 *
 * A contact's `page` column is a plain `group` relation to the default-language page
 * uid, so DataHandler `localize` copies it verbatim into every translation - whether
 * the page has a translation in the target language or not. Such a translated contact
 * carries no content of its own (every other column is `l10n_mode=exclude`) and makes
 * the contact appear twice on the page. The policy is therefore: a contact whose page
 * is not translated into the target language yields no translated contact at all.
 *
 * The guard hooks into `processCmdmap_afterFinish` because the localization of a
 * contact usually happens as part of an inline cascade (localizing a contract, a
 * profile, or synchronizing inline children): the children are localized through
 * internal `copyRecord`/`localize` calls, never through cmdmap entries of their own,
 * so per-record cmdmap callbacks do not fire for them. What does see them - verified
 * against a probed cascade on TYPO3 v13.4 and against the v14.3 sources - is
 * `DataHandler::$copyMappingArray_merged`, which accumulates every record the run
 * created, and `afterFinish` fires after `remapListedDBRecords()`, so the created
 * rows are fully wired when the guard inspects them.
 *
 * Offending rows are removed with a nested DataHandler `delete` command: for a live
 * row that is a soft delete, and for a row created in a workspace `deleteAction()`
 * dispatches to `discard()`, which removes the new placeholder entirely - workspace
 * semantics stay correct and nothing leaks into the live state.
 *
 * The guard only acts on connected translations (`sys_language_uid > 0` with a
 * `l10n_parent`). Copies to a language without a connection (`copyToLanguage`,
 * free mode) and plain same-language copies are left alone, as is everything that
 * is not a contact.
 */
final class DataHandlerHooks
{
    private const CONTACT_TABLE = 'tx_academiccontacts4pages_domain_model_contact';

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        $createdContactUids = $dataHandler->copyMappingArray_merged[self::CONTACT_TABLE] ?? [];
        if (!is_array($createdContactUids) || $createdContactUids === []) {
            return;
        }
        $workspaceId = $dataHandler->BE_USER->workspace;
        foreach ($createdContactUids as $createdContactUid) {
            $contactUid = (int)$createdContactUid;
            if ($contactUid <= 0) {
                continue;
            }
            $contact = BackendUtility::getRecord(self::CONTACT_TABLE, $contactUid);
            if ($contact === null) {
                continue;
            }
            $languageId = (int)($contact['sys_language_uid'] ?? 0);
            $pageUid = (int)($contact['page'] ?? 0);
            if ($languageId <= 0
                || (int)($contact['l10n_parent'] ?? 0) <= 0
                || $pageUid <= 0
                || $this->pageHasTranslation($pageUid, $languageId, $workspaceId)
            ) {
                continue;
            }
            $this->deleteContact($dataHandler, $contactUid);
        }
    }

    /**
     * Workspace-aware existence check: a page translated only in the acting workspace
     * counts as translated there, a translation existing only in another workspace
     * does not. Visibility is deliberately not part of the check - a hidden page
     * translation still is a translation.
     */
    private function pageHasTranslation(int $pageUid, int $languageId, int $workspaceId): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspaceId));
        $count = $queryBuilder
            ->count('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)
                ),
            )
            ->executeQuery()
            ->fetchOne();
        return ((int)$count) > 0;
    }

    /**
     * The nested run reuses the acting backend user, so permissions and the workspace
     * dispatch of `deleteAction()` apply unchanged. Its `afterFinish` callback fires
     * too, but sees an empty `copyMappingArray_merged` and returns immediately.
     */
    private function deleteContact(DataHandler $outerDataHandler, int $contactUid): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->enableLogging = $outerDataHandler->enableLogging;
        $dataHandler->start(
            [],
            [
                self::CONTACT_TABLE => [
                    $contactUid => [
                        'delete' => 1,
                    ],
                ],
            ],
            $outerDataHandler->BE_USER,
        );
        $dataHandler->process_cmdmap();
        if ($dataHandler->errorLog !== []) {
            $outerDataHandler->errorLog = array_merge($outerDataHandler->errorLog, $dataHandler->errorLog);
        }
    }
}
