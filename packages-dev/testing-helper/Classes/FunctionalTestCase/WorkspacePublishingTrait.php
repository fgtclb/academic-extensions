<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Edits records in a workspace and publishes it, the way an editor, the workspace module
 * and the scheduled publishing of a workspace do.
 *
 * The test case loads `typo3/cms-workspaces` and imports the administrator `be_users:1`
 * and the workspace it works in. Publishing uses the publish command `typo3/cms-workspaces`
 * builds, so it takes every version of the workspace, as core does. Each run starts a
 * backend user of its own, so `$GLOBALS['BE_USER']` is left behind and has to be unset in
 * `tearDown()`.
 */
trait WorkspacePublishingTrait
{
    /**
     * Runs a data map, then a command map, as the administrator in the given workspace, and
     * fails on any error `DataHandler` logged.
     *
     * @param array<string, array<int|string, array<string, mixed>>> $dataMap
     * @param array<string, array<int|string, array<string, mixed>>> $commandMap
     */
    private function runDataHandlerInWorkspace(int $workspaceId, array $dataMap, array $commandMap = []): DataHandler
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = $workspaceId;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, $commandMap, $backendUser);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');

        return $dataHandler;
    }

    /**
     * Publishes the whole workspace with the command core builds for it, from one of the two
     * places core publishes from:
     *
     * * `$fromTheWorkspace` true: the workspace module, whose backend user works in the
     *   workspace.
     * * `$fromTheWorkspace` false: `workspace:autopublish`, which runs for a workspace with a
     *   publish date, as a backend user in the live workspace.
     *
     * The two differ for inline children: published from the workspace, core writes the live
     * parent into the swapped children of a parent once more after the swap.
     */
    private function publishWorkspace(int $workspaceId, bool $fromTheWorkspace): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = $fromTheWorkspace ? $workspaceId : 0;
        $commandMap = $this->get(WorkspaceService::class)->getCmdArrayForPublishWS($workspaceId);
        $this->assertNotSame([], $commandMap, 'The workspace holds nothing to publish.');
        $this->runDataHandlerInWorkspace($fromTheWorkspace ? $workspaceId : 0, [], $commandMap);
    }

    /**
     * The uid of the version a live record has in the workspace, 0 for none.
     */
    private function getWorkspaceVersionUid(string $tableName, int $liveUid, int $workspaceId): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->select('uid')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter($liveUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter($workspaceId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
