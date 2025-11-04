<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Core13\Environment;

use FGTCLB\AcademicBase\Environment\StateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Provides a single environment snapshot populated by the current environment or used to create a new one.
 * Only for TYPO3 v13.
 *
 * Note that `#[Exclude]` is used intentionally to avoid automatic early compiling into the
 * dependency injection container leading to missing class and other issues for not related
 * TYPO3 version. TYPO3 version aware configuration is handled and re_enabled within the
 * `EXT:academic_base/Configuration/Services.php` file. This class is a DTO and should be
 * excluded always anyway.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
#[Exclude]
final class State implements StateInterface, ExtendedStateInterface
{
    /**
     * @param array<string, array<int|string, mixed>> $additionalData
     */
    public function __construct(
        private readonly ?ServerRequestInterface $request = null,
        private readonly ?TypoScriptFrontendController $typoScriptFrontendController = null,
        private readonly ?PreviewAspect $previewAspect = null,
        private readonly ?PageRenderer $pageRenderer = null,
        private readonly ?BackendUserAuthentication $backendUserAuthentication = null,
        private readonly array $additionalData = [],
    ) {}

    public function request(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function typoScriptFrontendController(): ?TypoScriptFrontendController
    {
        return $this->typoScriptFrontendController;
    }

    public function previewAspect(): ?PreviewAspect
    {
        return $this->previewAspect;
    }

    public function pageRenderer(): ?PageRenderer
    {
        return $this->pageRenderer;
    }

    public function backendUserAuthentication(): ?BackendUserAuthentication
    {
        return $this->backendUserAuthentication;
    }

    /**
     * @return array<int|string, mixed>|null Returns null in case $key does not exist, otherwise the data array.
     */
    public function additionalData(string $key): ?array
    {
        return array_key_exists($key, $this->additionalData)
            ? $this->additionalData[$key]
            : null;
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public function completeAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function withRequest(?ServerRequestInterface $request = null): self
    {
        return new self(
            request: $request,
            typoScriptFrontendController: $this->typoScriptFrontendController,
            previewAspect: $this->previewAspect,
            pageRenderer: $this->pageRenderer,
            backendUserAuthentication: $this->backendUserAuthentication,
            additionalData: $this->additionalData,
        );
    }

    public function withTypoScriptFrontendController(?TypoScriptFrontendController $typoScriptFrontendController = null): self
    {
        return new self(
            request: $this->request,
            typoScriptFrontendController: $typoScriptFrontendController,
            previewAspect: $this->previewAspect,
            pageRenderer: $this->pageRenderer,
            backendUserAuthentication: $this->backendUserAuthentication,
            additionalData: $this->additionalData,
        );
    }

    public function withPreviewAspect(?PreviewAspect $previewAspect = null): self
    {
        return new self(
            request: $this->request,
            typoScriptFrontendController: $this->typoScriptFrontendController,
            previewAspect: $previewAspect,
            pageRenderer: $this->pageRenderer,
            backendUserAuthentication: $this->backendUserAuthentication,
            additionalData: $this->additionalData,
        );
    }

    public function withPageRenderer(?PageRenderer $pageRenderer = null): self
    {
        return new self(
            request: $this->request,
            typoScriptFrontendController: $this->typoScriptFrontendController,
            previewAspect: $this->previewAspect,
            pageRenderer: $pageRenderer,
            backendUserAuthentication: $this->backendUserAuthentication,
            additionalData: $this->additionalData,
        );
    }

    public function withBackendUserAuthentication(?BackendUserAuthentication $backendUserAuthentication = null): self
    {
        return new self(
            request: $this->request,
            typoScriptFrontendController: $this->typoScriptFrontendController,
            previewAspect: $this->previewAspect,
            pageRenderer: $this->pageRenderer,
            backendUserAuthentication: $backendUserAuthentication,
            additionalData: $this->additionalData,
        );
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public function withAdditionalData(string $key, array $data): self
    {
        $additionalData = $this->additionalData;
        $additionalData[$key] = $data;
        return new self(
            request: $this->request,
            typoScriptFrontendController: $this->typoScriptFrontendController,
            previewAspect: $this->previewAspect,
            pageRenderer: $this->pageRenderer,
            backendUserAuthentication: $this->backendUserAuthentication,
            additionalData: $additionalData,
        );
    }
}
