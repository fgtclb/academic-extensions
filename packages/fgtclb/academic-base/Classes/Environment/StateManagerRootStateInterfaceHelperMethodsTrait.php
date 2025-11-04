<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment;

use FGTCLB\AcademicBase\Environment\Event\StateApplyEvent;
use FGTCLB\AcademicBase\Environment\Event\StateBackupEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This trait provides internal methods for {@see StateInterface} provided getter methods to use them
 * in {@see StateManagerInterface} implementation for the base interface and reduce code duplication.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
trait StateManagerRootStateInterfaceHelperMethodsTrait
{
    final protected function applyStateInterface(StateInterface $state): void
    {
        if (!$this instanceof StateManagerInterface) {
            throw new \RuntimeException(
                sprintf(
                    'Trait "%s" must be only used on classes implementing "%s", provided "%s" does not.',
                    StateManagerRootStateInterfaceHelperMethodsTrait::class,
                    StateManagerInterface::class,
                    static::class,
                ),
                1762340764,
            );
        }
        if ($state->request() !== null) {
            $GLOBALS['TYPO3_REQUEST'] = $state->request();
        } else {
            unset($GLOBALS['TYPO3_REQUEST']);
        }
        /** @var ContentObjectRenderer|null $contentObjectRenderer */
        $contentObjectRenderer = null;
        if ($state->typoScriptFrontendController() !== null) {
            $GLOBALS['TSFE'] = $state->typoScriptFrontendController();
            // New ServerRequest request is only applied to keep PHPStan for multiple core versions
            // happy, request should always exists in case a TypoScriptFrontendController exists in
            // state snapshot.
            $GLOBALS['TSFE']->newCObj($state->request() ?? new ServerRequest());
            $contentObjectRenderer = $GLOBALS['TSFE']->cObj;
        } else {
            unset($GLOBALS['TSFE']);
        }
        if ($state->backendUserAuthentication() !== null) {
            $GLOBALS['BE_USER'] = $state->backendUserAuthentication();
        } else {
            unset($GLOBALS['BE_USER']);
        }
        // Restore safed PageRender or clean it up at least.
        $instances = GeneralUtility::getSingletonInstances();
        unset($instances[PageRenderer::class]);
        GeneralUtility::resetSingletonInstances($instances);
        if ($state->pageRenderer() !== null) {
            GeneralUtility::setSingletonInstance(PageRenderer::class, $state->pageRenderer());
        }
        if ($contentObjectRenderer !== null) {
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
            if (method_exists($configurationManager, 'setRequest') && $state->request() !== null) {
                // TYPO3 v13
                $configurationManager->setRequest($state->request());
            }
            if (method_exists($configurationManager, 'setContentObject')) {
                // TYPO3 v12
                $configurationManager->setContentObject($contentObjectRenderer);
            }
        }
    }

    final protected function backupStateInterface(StateInterface $state): StateInterface
    {
        /** @var ServerRequestInterface|null $request */
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        /** @var TypoScriptFrontendController|null $typoScriptFrontendController */
        $typoScriptFrontendController = $GLOBALS['TSFE'] ?? null;
        $applicationType = $request !== null && $request->getAttribute('applicationType') ?: null;
        $pageRenderer = $request !== null && $applicationType !== null ? GeneralUtility::makeInstance(PageRenderer::class) : null;
        return $state
            ->withRequest($request)
            ->withTypoScriptFrontendController($typoScriptFrontendController)
            ->withBackendUserAuthentication($GLOBALS['BE_USER'] ?? null)
            ->withPageRenderer($pageRenderer);
    }

    final protected function dispatchStateApplyEvent(StateInterface $state): void
    {
        GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(new StateApplyEvent($state));
    }

    /**
     * @param StateInterface $state
     * @return StateInterface
     */
    final protected function dispatchStateBackupEvent(StateInterface $state): StateInterface
    {
        $event = new StateBackupEvent($state);
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);
        /** @var StateBackupEvent $event */
        return $event->getState();
    }
}
