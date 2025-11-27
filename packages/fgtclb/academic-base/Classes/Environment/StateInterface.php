<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Interface defining the shared methods across supported TYPO3 version,
 * used to implement version specific implementations.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
interface StateInterface
{
    public function withRequest(?ServerRequestInterface $request = null): self;
    public function request(): ?ServerRequestInterface;
    public function withTypoScriptFrontendController(?TypoScriptFrontendController $typoScriptFrontendController = null): self;
    public function typoScriptFrontendController(): ?TypoScriptFrontendController;
    public function withPageRenderer(?PageRenderer $pageRenderer = null): self;
    public function pageRenderer(): ?PageRenderer;
    public function withBackendUserAuthentication(?BackendUserAuthentication $backendUserAuthentication = null): self;
    public function backendUserAuthentication(): ?BackendUserAuthentication;
}
